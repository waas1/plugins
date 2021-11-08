export function moduleName() {
  return "site-devices";
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
              label: "Site Users",
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
        noAccount: false,
        output: [],
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
          return "horizontalbar";
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
            action: "uipress_analytics_get_site_devices",
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

            self.GAaccount = true;
            self.chartData = responseData.dataSet;
            self.output = responseData.output;

            self.loading = false;
          },
        });
      },
    },
    template:
      '<div style="padding: var(--a2020-card-padding);position:relative" :accountConnected="isGAconnected">\
	  	<loading-placeholder v-if="loading == true"></loading-placeholder>\
		  <loading-placeholder v-if="loading == true"></loading-placeholder>\
      <premium-overlay v-if="!premium" :translations="translations"></premium-overlay>\
      <connect-google-analytics @account-connected="getData()" :translations="translations" v-if="loading != true && !isGAconnected"></connect-google-analytics>\
      <div v-if="!editingMode && loading != true && isGAconnected" class="uk-grid">\
        <div class="uk-width-1-1@s uk-width-2-3@m">\
          <uip-chart :removeLabels="true" :dates="getTheDates" v-if="loading != true" :type="getTheType" :chartData="chartData"  :gridLines="false" cWidth="200px"></uip-chart>\
        </div>\
        <div class="uk-width-1-1@s uk-width-1-3@m ">\
          <div v-for="item in output" class="uk-flex uk-flex-middle uk-margin-small-bottom " >\
            <span style="width:10px; height:10px;border-radius:50%" :style="{\'background\' : item.color}" class="uk-margin-small-right"></span>\
            <span class="uk-text-muted uk-margin-small-right">{{item.name}}:</span>\
            <span class="uk-text-bold">{{item.value}}</span>\
          </div>\
        </div>\
      </div>\
      <form v-if="editingMode" class="uk-form-stacked" style="padding: var(--a2020-card-padding);">\
      <div class="uk-margin">\
          <label class="uk-form-label" for="form-stacked-select">{{translations.chartType}}</label>\
          <div class="uk-form-controls">\
              <select class="uk-select"  v-model="cardOptions.chartType">\
                  <option value="doughnut">{{translations.doughnut}}</option>\
                  <option value="polarArea">{{translations.polarArea}}</option>\
                  <option value="bar">{{translations.bar}}</option>\
                  <option value="horizontalbar">{{translations.hbar}}</option>\
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
