export function moduleName() {
  return "bounce-rate";
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
        cardOptions: this.cardData,
        loading: true,
        numbers: [],
        startDate: this.dateRange.startDate,
        noAccount: false,
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

        jQuery.ajax({
          url: uipress_overview_ajax.ajax_url,
          type: "post",
          data: {
            action: "uipress_analytics_get_bounce_rate",
            security: uipress_overview_ajax.security,
            dates: self.getTheDates,
          },
          success: function (response) {
            var responseData = JSON.parse(response);

            if (responseData.error) {
              ///SOMETHING WENT WRONG
              UIkit.notification(responseData.error, { pos: "bottom-left", status: "danger" });
              self.loading = false;
              return;
            }

            if (responseData.noaccount) {
              ///SOMETHING WENT WRONG
              self.GAaccount = false;
              self.loading = false;
              return;
            }

            self.noAccount = false;
            self.loading = false;
            self.numbers = responseData.numbers;
          },
        });
      },
    },
    template:
      '<div style="padding: var(--a2020-card-padding);position:relative" :accountConnected="isGAconnected" >\
	  	<loading-placeholder v-if="loading == true"></loading-placeholder>\
      <premium-overlay v-if="!premium" :translations="translations"></premium-overlay>\
      <connect-google-analytics @account-connected="getData()" :translations="translations" v-if="loading != true && !isGAconnected"></connect-google-analytics>\
      <div v-if="loading != true  && isGAconnected" class="uk-grid uk-grid-small">\
        <div class="uk-width-expand uk-flex uk-flex-middle ">\
          <div class="uk-h2 uk-text-bold uk-margin-small-right" style="margin:0;margin-right:10px">{{numbers.total}}%</div>\
          <span class="a2020-post-label" :class="{\'uk-text-danger\' : numbers.change > 0}">\
            <span v-if="numbers.change > 0" class="material-icons-outlined" \
            style="font-size: 18px;line-height: 0;top: 5px;position: relative;">expand_less</span>\
            <span v-if="numbers.change < 0" class="material-icons-outlined" \
            style="font-size: 18px;line-height: 0;top: 5px;position: relative;">expand_more</span>\
            {{numbers.change}}%\
          </span>\
          </div>\
        <div class="uk-width-1-1">\
            <div class="uk-text-meta">{{translations.vsPrevious}} {{daysDif}} {{translations.vsdays}} ({{numbers.total_comparison}}%)</div>\
        </div>\
      </div>\
		 </div>',
  };
  return compData;
}

export default function () {
  console.log("Loaded");
}
