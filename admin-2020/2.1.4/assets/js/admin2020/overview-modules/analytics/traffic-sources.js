export function moduleName() {
  return "traffic-sources";
}

export function moduleData() {
  return {
    props: {
      chartData: Object,
      dateRange: Object,
      translations: Object,
      editingMode: Boolean,
      premium: Boolean,
      analytics: Boolean,
    },
    data: function () {
      return {
        tableData: {},
        loading: true,
        numbers: [],
        startDate: this.dateRange.startDate,
        countries: Object,
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
      getTheDates() {
        return this.dateRange;
      },
      getPostsOnce() {
        this.getPosts();
      },
      isGAconnected() {
        return this.analytics;
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
            action: "uipress_analytics_get_sources",
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
            self.loading = false;
            self.tableData = responseData.dataSet;
          },
        });
      },
      defaultImage(e) {
        jQuery(e.target).replaceWith('<span class="material-icons-outlined" style="font-size: 20px;margin-right: 10px;">flag</span>');
      },
    },
    template:
      '<div style="padding: var(--a2020-card-padding);position:relative" :accountConnected="isGAconnected">\
  	  	<loading-placeholder v-if="loading == true"></loading-placeholder>\
  		  <loading-placeholder v-if="loading == true"></loading-placeholder>\
        <premium-overlay v-if="!premium" :translations="translations"></premium-overlay>\
        <connect-google-analytics @account-connected="getData()" :translations="translations" v-if="loading != true && !isGAconnected"></connect-google-analytics>\
        <div v-if="loading != true  && isGAconnected" class="uk-overflow-auto">\
          <table class="uk-table uk-table-small uk-table-justify">\
            <thead>\
                <tr>\
                    <th>{{translations.source}}</th>\
                    <th>{{translations.visits}}</th>\
                    <th class="uk-text-right">{{translations.change}}</th>\
                </tr>\
            </thead>\
            <tbody>\
                <tr v-for="item in tableData">\
                    <td>\
                    <img :src="item.flag" alt="flag" @error="defaultImage" style="width:20px;margin-right:10px;border-radius:2px;">\
                      {{item.name}}\
                    </td>\
                    <td>{{item.visits}}</td>\
                    <td class="uk-text-right">\
                      <span class="a2020-post-label" :class="{\'uk-text-danger\' : item.change < 0}">\
                        <span v-if="item.change > 0" class="material-icons-outlined" \
                        style="font-size: 18px;line-height: 0;top: 5px;position: relative;">expand_less</span>\
                        <span v-if="item.change < 0" class="material-icons-outlined" \
                        style="font-size: 18px;line-height: 0;top: 5px;position: relative;">expand_more</span>\
                        {{item.change}}%\
                      </span>\
                    </td>\
                </tr>\
            </tbody>\
          </table>\
        </div>\
		 </div>',
  };
  return compData;
}

export default function () {
  console.log("Loaded");
}
