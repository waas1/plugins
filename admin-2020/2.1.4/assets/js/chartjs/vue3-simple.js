const r = [
  "install",
  "start",
  "stop",
  "beforeInit",
  "afterInit",
  "beforeUpdate",
  "afterUpdate",
  "beforeElementsUpdate",
  "reset",
  "beforeDatasetsUpdate",
  "afterDatasetsUpdate",
  "beforeDatasetUpdate",
  "afterDatasetUpdate",
  "beforeLayout",
  "afterLayout",
  "afterLayout",
  "beforeRender",
  "afterRender",
  "resize",
  "destroy",
  "uninstall",
  "afterTooltipDraw",
  "beforeTooltipDraw",
];

const vue3chart3 = {
  name: "Vue3ChartJs",
  props: { type: { type: String, required: !0 }, data: { type: Object, required: !0 }, options: { type: Object, default: () => ({}) }, plugins: { type: Array, default: () => [] } },
  emits: r,
  setup(a, { emit: o }) {
    const n = e.ref(null),
      l = {
        chart: null,
        plugins: [
          r.reduce(
            (e, t) => {
              const r = (function (e, t = null) {
                return {
                  type: e,
                  chartRef: t,
                  preventDefault() {
                    this._defaultPrevented = !0;
                  },
                  isDefaultPrevented() {
                    return !this._defaultPrevented;
                  },
                  _defaultPrevented: !1,
                };
              })(t, n);
              return __spreadValues(
                __spreadValues({}, e),
                (function (e, t) {
                  return { [t.type]: () => (e(t.type, t), t.isDefaultPrevented()) };
                })(o, r)
              );
            },
            { id: "Vue3ChartJsEventHookPlugin" }
          ),
          ...a.plugins,
        ],
        props: __spreadValues({}, a),
      },
      s = () => (l.chart ? l.chart.update() : (l.chart = new t.Chart(n.value.getContext("2d"), { type: l.props.type, data: l.props.data, options: l.props.options, plugins: l.plugins })));
    return (
      e.onMounted(() => s()),
      {
        chartJSState: l,
        chartRef: n,
        render: s,
        resize: () => l.chart && l.chart.resize(),
        update: (e = 750) => {
          (l.chart.data = __spreadValues(__spreadValues({}, l.chart.data), l.props.data)), (l.chart.options = __spreadValues(__spreadValues({}, l.chart.options), l.props.options)), l.chart.update(e);
        },
        destroy: () => {
          l.chart && (l.chart.destroy(), (l.chart = null));
        },
      }
    );
  },
  render: () => e.h("canvas", { ref: "chartRef" }),
};
