export function moduleName() {
  return "page-speed";
}

export function moduleData() {
  return {
    props: {
      cardData: Object,
      dateRange: Object,
      translations: Object,
      editingMode: Boolean,
      premium: Boolean,
      analytics: Boolean,
    },
    data: function () {
      return {
        chartData: {
          labels: ["30th July 21", "31st July 21", "1st August 21", "2nd August 21", "3rd August 21", "4th August 21"],
          datasets: [
            {
              label: "Page Views",
              fill: true,
              data: [12, 19, 3, 5, 2, 3],
              backgroundColor: ["rgba(12, 92, 239, 0.05)"],
              borderColor: ["rgba(12, 92, 239, 1)"],
              borderWidth: 2,
              cutout: "0%",
              spacing: 0,
              borderRadius: 0,
              tension: 0.2,
              pointRadius: 0,
            },
          ],
        },
        cardOptions: this.cardData,
        loading: true,
        numbers: [],
        startDate: this.dateRange.startDate,
        GAaccount: this.analytics,
      };
    },
    mounted: function () {
      this.loading = false;
      this.getData();
    },
    watch: {
      dateRange: function (newValue, oldValue) {
        if (newValue.startDate != oldValue.startDate || newValue.endDate != oldValue.endDate) {
          this.getData();
        }
      },
    },
    computed: {
      isGAconnected() {
        return this.analytics;
      },
      getTheDates() {
        return this.dateRange;
      },
      getPostsOnce() {
        this.getPosts();
      },
      formattedPosts() {
        this.getPostsOnce;
        return this.recentPosts;
      },
      getTheType() {
        let self = this;
        if (self.cardOptions.chartType) {
          return self.cardOptions.chartType;
        } else {
          return "line";
        }
      },
      daysDif() {
        self = this;
        var b = moment(self.dateRange.startDate);
        var a = moment(self.dateRange.endDate);
        return a.diff(b, "days");
      },
    },
    methods: {
      getData() {
        let self = this;
        self.loading = true;

        if (!self.isGAconnected) {
          self.loading = false;
          return;
        }

        jQuery.ajax({
          url: uipress_overview_ajax.ajax_url,
          type: "post",
          data: {
            action: "uipress_analytics_get_page_speed",
            security: uipress_overview_ajax.security,
            dates: self.getTheDates,
          },
          success: function (response) {
            var responseData = JSON.parse(response);

            if (responseData.noaccount) {
              ///SOMETHING WENT WRONG
              self.GAaccount = false;
              self.loading = false;
              return;
            }

            self.loading = false;
            self.GAaccount = true;
            self.chartData = responseData.dataSet;
            self.numbers = responseData.numbers;
          },
        });
      },
    },
    template:
      '<div style="padding: var(--a2020-card-padding);position:relative" :accountConnected="isGAconnected">\
	  	<loading-placeholder v-if="loading == true"></loading-placeholder>\
		  <loading-placeholder v-if="loading == true"></loading-placeholder>\
      <connect-google-analytics @account-connected="getData()" :translations="translations" v-if="loading != true && !isGAconnected"></connect-google-analytics>\
      <premium-overlay v-if="!premium" :translations="translations" ></premium-overlay>\
      <div v-if="!editingMode && loading != true && isGAconnected" class="uk-grid uk-grid-small">\
        <div class="uk-width-1-1 uk-flex uk-flex-middle ">\
          <span  class="material-icons-outlined uk-margin-small-right" style="font-size:30px;">speed</span>\
          <div class="uk-h2 uk-text-bold uk-margin-small-right" style="margin:0;margin-right:10px">{{numbers.total}}s</div>\
          <span class="a2020-post-label" :class="{\'uk-text-danger\' : numbers.change > 0}">\
            <span v-if="numbers.change > 0" class="material-icons-outlined" \
            style="font-size: 18px;line-height: 0;top: 5px;position: relative;">expand_less</span>\
            <span v-if="numbers.change < 0" class="material-icons-outlined" \
            style="font-size: 18px;line-height: 0;top: 5px;position: relative;">expand_more</span>\
            {{numbers.change}}%\
          </span>\
        </div>\
        <div class="uk-width-1-1 uk-margin-large-bottom uk-flex uk-flex-middle">\
            <div class="uk-text-meta">{{translations.vsPrevious}} {{daysDif}} {{translations.vsdays}} ({{numbers.total_comparison}})</div>\
        </div>\
        <div class="uk-width-1-1">\
          <uip-chart :dates="getTheDates" v-if="loading != true" :type="getTheType" :chartData="chartData"  :gridLines="true" cWidth="200px"></uip-chart>\
        </div>\
      </div>\
      <form v-if="editingMode" class="uk-form-stacked" style="padding: var(--a2020-card-padding);">\
      <div class="uk-margin">\
          <label class="uk-form-label" for="form-stacked-select">{{translations.chartType}}</label>\
          <div class="uk-form-controls">\
              <select class="uk-select"  v-model="cardOptions.chartType">\
                  <option value="line">{{translations.lineChart}}</option>\
                  <option value="bar">{{translations.barChart}}</option>\
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
