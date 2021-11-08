export function moduleName() {
  return "custom-video";
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
      };
    },
    mounted: function () {
      this.loading = false;
    },
    computed: {},
    methods: {},
    template:
      '<div style="position:relative">\
  	  	<loading-placeholder v-if="loading == true"></loading-placeholder>\
  		  <loading-placeholder v-if="loading == true"></loading-placeholder>\
        <premium-overlay v-if="!premium" :translations="translations"></premium-overlay>\
        <div v-if="!editingMode" style="padding-top:15px;">\
          <iframe v-if="cardOptions.videotype == \'vimeo\' || cardOptions.videotype == \'youtube\'" \
          :src="cardOptions.videoURL" width="1920" height="1080" frameborder="0" \
          allowfullscreen uk-responsive uk-video="automute: false;autoplay: false"></iframe>\
          <video v-if="cardOptions.videotype == \'direct\'" :src="cardOptions.videoURL" controls uk-video="autoplay: false"></video>\
        </div>\
        <form v-if="editingMode" class="uk-form-stacked" style="padding: var(--a2020-card-padding);">\
          <div class="uk-margin">\
              <label class="uk-form-label" for="form-stacked-text">{{translations.title}}</label>\
              <div class="uk-form-controls">\
                  <input class="uk-input uk-form-small"  type="text" v-model="cardOptions.name" :placeholder="translations.title">\
              </div>\
          </div>\
          <div class="uk-margin">\
              <label class="uk-form-label" for="form-stacked-text">{{translations.videourl}}</label>\
              <div class="uk-form-controls">\
                  <input class="uk-input uk-form-small"  v-model="cardOptions.videoURL" type="text" :placeholder="translations.videourl">\
              </div>\
          </div>\
          <div class="uk-margin">\
              <label class="uk-form-label" for="form-stacked-select">{{translations.embedType}}</label>\
              <div class="uk-form-controls">\
                  <select class="uk-select" id="form-stacked-select" v-model="cardOptions.videotype">\
                      <option value="vimeo">Vimeo (iframe)</option>\
                      <option value="youtube">Youtube (iframe)</option>\
                      <option value="direct">Direct Link to video</option>\
                  </select>\
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
