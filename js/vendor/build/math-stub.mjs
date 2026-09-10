const noop = () => undefined;
const handler = {
    get: (target, prop) => (prop === 'default' ? proxy : (typeof prop === 'string' ? proxy : undefined)),
    apply: noop,
    construct: () => ({}),
};
const proxy = new Proxy(noop, handler);
export default proxy;
export const mathjax = proxy;
export const TeX = proxy;
export const SVG = proxy;
export const CHTML = proxy;
export const liteAdaptor = proxy;
export const RegisterHTMLHandler = noop;
export const AllPackages = [];
export const renderToString = () => '';
