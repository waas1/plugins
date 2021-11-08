export function moduleName() {
  return "shortcode";
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
      this.getShortCode();

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
      getShortCode() {
        let self = this;
        if (!this.cardOptions.shortcode || this.cardOptions.shortcode.length < 1) {
          return;
        }

        self.loading = true;
        jQuery.ajax({
          url: uipress_overview_ajax.ajax_url,
          type: "post",
          data: {
            action: "uipress_get_shortcode",
            security: uipress_overview_ajax.security,
            shortCode: self.cardOptions.shortcode.replace(/\\(.)/gm, "$1"),
          },
          success: function (response) {
            var responseData = JSON.parse(response);

            if (responseData.error) {
              ///SOMETHING WENT WRONG
              UIkit.notification(responseData.error, { pos: "bottom-left", status: "danger" });
              self.loading = false;
              return;
            }

            self.shortCode = responseData.shortCode;
            self.loading = false;
          },
        });
      },
    },
    template:
      '<div style="position:relative;padding: var(--a2020-card-padding);" style="position:relative">\
        <premium-overlay v-if="!premium" :translations="translations"></premium-overlay>\
  	  	<loading-placeholder v-if="loading == true"></loading-placeholder>\
  		  <loading-placeholder v-if="loading == true"></loading-placeholder>\
        <div v-if="!editingMode" style="padding-top:15px;" v-html="shortCode">\
        </div>\
        <form v-if="editingMode" class="uk-form-stacked" >\
          <div class="uk-margin">\
              <label class="uk-form-label" for="form-stacked-text">{{translations.title}}</label>\
              <div class="uk-form-controls">\
                  <input class="uk-input uk-form-small"  type="text" v-model="cardOptions.name" :placeholder="translations.title">\
              </div>\
          </div>\
          <div class="uk-margin">\
              <label class="uk-form-label" for="form-stacked-text">{{translations.shortcode}}</label>\
              <div class="uk-form-controls">\
                  <input class="uk-input uk-form-small"  v-model="strippedShort" type="text" :placeholder="translations.shortcode">\
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
