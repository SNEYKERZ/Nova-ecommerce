/**
 * Mosaico editorial para galerías (sin espacios en blanco).
 *
 * Cada foto trae la proporción que el dueño eligió al subirla (1/1, 2/3, 3/2…).
 * El mosaico reparte las fotos en columnas, en el orden en que se subieron:
 *
 * 1. Cada foto cae en la columna más corta, con su proporción exacta.
 * 2. Las horizontales y, cada cierto número, una foto destacada ocupan dos
 *    columnas. Antes de colocarla, las dos columnas se igualan estirando
 *    levemente la última foto de la más corta (recorte con object-fit: cover).
 *    Si ese estirón recortaría demasiado, la foto ocupa una sola columna.
 * 3. Al final todas las columnas se cierran a la misma altura ajustando la
 *    última foto de cada una.
 *
 * Se prueban varias variantes (qué fotos del final pueden ir anchas, desde qué
 * lado se llenan las columnas) y se queda la que cierra pareja con el menor
 * recorte. Trabaja en píxeles a partir del ancho real del contenedor.
 */

const MAX_ALIGN_STRETCH = 0.32; // estirón máximo al alinear columnas
const MIN_TAIL_SCALE = 0.55;    // la última foto puede recortarse hasta el 55 % de su altura
const MAX_TAIL_SCALE = 1.7;     // …o estirarse hasta el 170 %

export function parseRatio(ratio) {
  const match = /^(\d{1,2})\/(\d{1,2})$/.exec(String(ratio || ''));
  if (!match) return 1;
  const value = Number(match[1]) / Number(match[2]);
  return Number.isFinite(value) && value > 0 ? value : 1;
}

/** Columnas según el ancho del contenedor. */
export function mosaicColumns(width) {
  if (width < 560) return 2;
  if (width < 900) return 3;
  if (width < 1400) return 4;
  return 5;
}

function buildLayout(ratios, cols, colW, gap, variant) {
  const spanWidth = (span) => span * colW + (span - 1) * gap;
  const heights = new Array(cols).fill(0);
  const last = new Array(cols).fill(null);
  const boxes = [];

  const isTail = (index) => {
    const box = boxes[index];
    for (let c = box.col; c < box.col + box.span; c++) if (last[c] !== index) return false;
    return true;
  };
  const resize = (index, h) => {
    const box = boxes[index];
    box.h = h;
    for (let c = box.col; c < box.col + box.span; c++) heights[c] = box.y + h + gap;
  };
  const align = (c) => {
    const target = Math.max(heights[c], heights[c + 1]);
    for (let k = c; k <= c + 1; k++) {
      const deficit = target - heights[k];
      if (deficit < 0.5) continue;
      const tail = last[k];
      if (tail === null || !isTail(tail) || deficit / boxes[tail].h > MAX_ALIGN_STRETCH) return false;
    }
    for (let k = c; k <= c + 1; k++) {
      const deficit = target - heights[k];
      if (deficit >= 0.5) resize(last[k], boxes[last[k]].h + deficit);
    }
    return true;
  };
  const place = (index, col, span) => {
    const y = Math.max(...heights.slice(col, col + span));
    const w = spanWidth(span);
    boxes[index] = { x: col * (colW + gap), y, w, h: w / ratios[index], span, col, ratio: ratios[index] };
    for (let c = col; c < col + span; c++) { heights[c] = y + boxes[index].h + gap; last[c] = index; }
  };

  const n = ratios.length;
  ratios.forEach((ratio, index) => {
    const featured = variant.featureEvery > 0 && cols >= 4 && index % variant.featureEvery === 0;
    const wideAllowed = cols >= 3 && index < n - variant.narrowTail;
    if (wideAllowed && (ratio > 1.2 || featured)) {
      const pairs = [];
      for (let c = 0; c + 1 < cols; c++) {
        const top = Math.max(heights[c], heights[c + 1]);
        pairs.push({ c, score: top + Math.abs(heights[c] - heights[c + 1]) * 1.5 });
      }
      pairs.sort((a, b) => a.score - b.score);
      const pair = pairs.find(({ c }) => align(c));
      if (pair) { place(index, pair.c, 2); return; }
    }
    let col = variant.fromRight ? cols - 1 : 0;
    for (let i = 0; i < cols; i++) {
      const c = variant.fromRight ? cols - 1 - i : i;
      if (heights[c] < heights[col] - 0.5) col = c;
    }
    place(index, col, 1);
  });

  // Cierre parejo
  const tailOf = (c) => (last[c] !== null && isTail(last[c]) ? last[c] : null);
  const pinned = [];
  const tails = new Set();
  for (let c = 0; c < cols; c++) {
    const t = tailOf(c);
    if (t === null) pinned.push(heights[c] - gap);
    else tails.add(t);
  }

  let bottom;
  if (pinned.length) {
    const lo = Math.min(...pinned);
    const hi = Math.max(...pinned);
    if (hi - lo > 1) return { boxes, cost: Infinity, gapArea: (hi - lo) * colW };
    bottom = hi;
  }

  const range = [...tails].map((i) => {
    const box = boxes[i];
    return { i, lo: box.y + box.h * MIN_TAIL_SCALE, hi: box.y + box.h * MAX_TAIL_SCALE, natural: box.y + box.h };
  });

  if (bottom === undefined) {
    // Línea que minimiza la deformación máxima: probar los fondos naturales y el punto medio.
    const options = range.map((r) => r.natural);
    if (options.length) options.push((Math.min(...options) + Math.max(...options)) / 2);
    let best = null;
    options.forEach((b) => {
      const cost = Math.max(0, ...range.map((r) => Math.abs(b - r.natural) / boxes[r.i].h));
      if (!best || cost < best.cost) best = { b, cost };
    });
    bottom = best ? best.b : 0;
  }

  const infeasible = range.filter((r) => bottom < r.lo - 0.5 || bottom > r.hi + 0.5);
  const cost = Math.max(0, ...range.map((r) => Math.abs(bottom - r.natural) / boxes[r.i].h));
  range.forEach((r) => resize(r.i, Math.max(1, bottom - boxes[r.i].y)));

  return {
    boxes,
    bottom,
    cost: infeasible.length ? 10 + cost : cost,
    gapArea: 0,
  };
}

/**
 * Filas justificadas: cada fila llena el ancho exacto y todas las fotos de la
 * fila comparten altura, así ninguna se deforma. Se usa con pocas fotos y como
 * respaldo si el mosaico no logra cerrar parejo.
 */
function justifiedRows(ratios, width, gap, targetHeight) {
  const rows = [];
  let current = [];
  ratios.forEach((ratio, index) => {
    current.push(index);
    const sum = current.reduce((acc, i) => acc + ratios[i], 0);
    const h = (width - gap * (current.length - 1)) / sum;
    if (h <= targetHeight) { rows.push(current); current = []; }
  });
  if (current.length) {
    // La última fila no debe quedar gigante: si es muy alta se une a la anterior.
    const sum = current.reduce((acc, i) => acc + ratios[i], 0);
    const h = (width - gap * (current.length - 1)) / sum;
    if (rows.length && h > targetHeight * 1.6) rows[rows.length - 1].push(...current);
    else rows.push(current);
  }

  const boxes = [];
  let y = 0;
  rows.forEach((row) => {
    const sum = row.reduce((acc, i) => acc + ratios[i], 0);
    const h = (width - gap * (row.length - 1)) / sum;
    let x = 0;
    row.forEach((i, k) => {
      const w = k === row.length - 1 ? width - x : h * ratios[i];
      boxes[i] = { x, y, w, h, span: 1 };
      x += w + gap;
    });
    y += h + gap;
  });
  return { boxes, height: Math.max(0, y - gap) };
}

/**
 * @param {Array<{aspect_ratio?: string}>} items
 * @param {number} width  ancho del contenedor en px
 * @param {{gap?: number, columns?: number, featureEvery?: number}} options
 * @returns {{boxes: Array<{x:number,y:number,w:number,h:number,span:number}>, height:number, columns:number}}
 */
export function layoutMosaic(items, width, { gap = 12, columns, featureEvery = 7 } = {}) {
  if (!items?.length || width <= 0) return { boxes: [], height: 0, columns: 1 };

  const ratios = items.map((item) => parseRatio(item?.aspect_ratio));
  const baseCols = Math.max(1, columns || mosaicColumns(width));
  const rowTarget = Math.min(560, (width / baseCols) * 1.35);

  // Pocas fotos: una fila justificada se ve mejor que columnas medio vacías.
  if (items.length <= Math.max(2, baseCols - 1)) {
    const { boxes, height } = justifiedRows(ratios, width, gap, rowTarget * 1.4);
    return { boxes, height, columns: baseCols };
  }

  let best = null;
  for (const cols of [baseCols, baseCols - 1].filter((c) => c >= 2)) {
    const colW = (width - gap * (cols - 1)) / cols;
    const tailOptions = [];
    for (let k = 0; k <= Math.min(items.length, cols * 2); k++) tailOptions.push(k);

    for (const feature of [featureEvery, 0]) {
      for (const fromRight of [false, true]) {
        for (const narrowTail of tailOptions) {
          const result = buildLayout(ratios, cols, colW, gap, { featureEvery: feature, fromRight, narrowTail });
          // Una columna menos sólo gana si la base no logra un cierre aceptable.
          const penalty = cols === baseCols ? 0 : 0.35;
          const score = (result.gapArea > 0 ? 1000 + result.gapArea : result.cost) + penalty;
          if (!best || score < best.score - 1e-6) best = { ...result, score, cols };
          if (best.score < 0.08) break;
        }
        if (best.score < 0.08) break;
      }
      if (best.score < 0.08) break;
    }
    if (best.score < 0.5) break;
  }

  if (!best || best.score >= 10) {
    const { boxes, height } = justifiedRows(ratios, width, gap, rowTarget);
    return { boxes, height, columns: baseCols };
  }

  const boxes = best.boxes.map(({ x, y, w, h, span }) => ({ x, y, w, h, span }));
  const height = Math.max(...boxes.map((b) => b.y + b.h));
  return { boxes, height, columns: best.cols };
}
