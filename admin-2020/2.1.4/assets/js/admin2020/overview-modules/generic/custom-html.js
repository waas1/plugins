export function moduleName() {
  return "custom-html";
}

export function moduleData() {
  return {
    props: {
      cardData: Object,
      dateRange: Object,
      translations: Object,
      editingMode: Boolean,
      premium: Boolean,
    },
    data: function () {
      return {
        cardOptions: this.cardData,
        loading: true,
        strippedShort: "",
        shortCode: "",
      };
    },
    mounted: function () {
      this.loading = false;

      if (this.cardOptions.shortcode) {
        this.strippedShort = this.cardOptions.shortcode.replace(/\\(.)/gm, "$1");
      }
    },
    watch: {
      strippedShort: function (newValue, oldValue) {
        this.cardOptions.shortcode = this.strippedShort;
      },
    },
    computed: {},
    methods: {
      getDataFromComp(code) {
        return code;
      },
    },
    template:
      '<div style="position:relative;padding: var(--a2020-card-padding);">\
        <premium-overlay v-if="!premium" :translations="translations"></premium-overlay>\
  	  	<loading-placeholder v-if="loading == true"></loading-placeholder>\
  		  <loading-placeholder v-if="loading == true"></loading-placeholder>\
        <div v-if="!editingMode" style="padding-top:15px;" v-html="strippedShort">\
        </div>\
        <form v-if="editingMode" class="uk-form-stacked" >\
          <div class="uk-margin">\
              <label class="uk-form-label" for="form-stacked-text">{{translations.title}}</label>\
              <div class="uk-form-controls">\
                  <input class="uk-input uk-form-small"  type="text" v-model="cardOptions.name" :placeholder="translations.title">\
              </div>\
          </div>\
          <div class="uk-margin">\
              <label class="uk-form-label" for="form-stacked-text">HTML</label>\
              <div class="uk-form-controls">\
                  <code-flask  language="HTML"  :usercode="strippedShort" \
                  @code-change="strippedShort = getDataFromComp($event)"></code-flask>\
              </div>\
          </div>\
        </form>\
		 </div>',
  };
  return compData;
}

export default function () {
  console.log("Loaded");
}
