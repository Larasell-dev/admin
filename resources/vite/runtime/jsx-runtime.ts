export const jsx = (...args: Parameters<Window['LarasellAdmin']['JsxRuntime']['jsx']>) =>
  window.LarasellAdmin.JsxRuntime.jsx(...args);

export const jsxs = (...args: Parameters<Window['LarasellAdmin']['JsxRuntime']['jsxs']>) =>
  window.LarasellAdmin.JsxRuntime.jsxs(...args);

export const Fragment = window.LarasellAdmin.JsxRuntime.Fragment;
