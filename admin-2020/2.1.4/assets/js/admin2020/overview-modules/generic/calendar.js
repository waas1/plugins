export function moduleName() {
  return "calendar";
}

export function moduleData() {
  return {
    props: {
      cardData: Object,
      dateRange: Object,
      translations: Object,
    },
    data: function () {
      return {
        cardOptions: this.cardData,
        loading: true,
        date: {
          fullDay: "",
          numberDay: "",
          hour: "",
          minute: "",
          second: "",
          ampm: "",
        },
      };
    },
    mounted: function () {
      this.loading = false;
      this.setTime();
      let clock = this;
      //setTimeout(clock.setTime(), 1000);
      window.setInterval(() => {
        clock.setTime();
      }, 1000);
    },
    computed: {},
    methods: {
      setTime() {
        let clock = this;
        clock.date.fullDay = moment().format("dddd");
        clock.date.numberDay = moment().format("Do");
        clock.date.hour = moment().format("h");
        clock.date.minute = moment().format("mm");
        clock.date.second = moment().format("ss");
        clock.date.ampm = moment().format("a");
      },
    },
    template:
      '<div style="padding: var(--a2020-card-padding);">\
  	  	<loading-placeholder v-if="loading == true"></loading-placeholder>\
  		  <loading-placeholder v-if="loading == true"></loading-placeholder>\
        <div class="uk-grid uk-grid-small">\
          <div class="uk-width-1-1">\
            <span class="uk-h4 uk-margin-remove uk-text-primary">{{date.fullDay}}</span>\
          </div>\
          <div class="uk-width-1-1 uk-margin-bottom ">\
  		     <span class="uk-h1 uk-text-bold">{{date.numberDay}}</span>\
          </div>\
          <div class="uk-width-1-1">\
           <span class="uk-h5 a2020-post-label uk-text-bold">{{date.hour}}</span>\
           <span class="uk-h5 a2020-post-label  uk-text-bold">{{date.minute}}</span>\
           <span class="uk-h5 a2020-post-label  uk-text-bold">{{date.second}}</span>\
           <span class="uk-h5 a2020-post-label  uk-text-bold">{{date.ampm}}</span>\
          </div>\
        </div>\
		 </div>',
  };
  return compData;
}

export default function () {
  console.log("Loaded");
}
