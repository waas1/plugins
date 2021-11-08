export function moduleName() {
  return "recent-orders";
}

export function moduleData() {
  return {
    props: {
      cardData: Object,
      dateRange: Object,
      translations: Object,
      premium: Boolean,
    },
    data: function () {
      return {
        cardOptions: this.cardData,
        width: "uk-width-" + this.cardData.size,
        recentPosts: [],
        currentPage: 1,
        startDate: this.dateRange.startDate,
        maxPage: 1,
        totalFound: 0,
        loading: true,
        nonfound: "",
        woocommerce: true,
      };
    },
    mounted: function () {
      this.loading = false;
    },
    computed: {
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
      daysDif() {
        self = this;
        var b = moment(self.dateRange.startDate);
        var a = moment(self.dateRange.endDate);
        return a.diff(b, "days");
      },
    },
    methods: {
      getPosts() {
        let self = this;
        self.loading = true;

        jQuery.ajax({
          url: uipress_overview_ajax.ajax_url,
          type: "post",
          data: {
            action: "uipress_get_recent_orders",
            security: uipress_overview_ajax.security,
            dates: self.getTheDates,
            currentPage: self.currentPage,
          },
          success: function (response) {
            var responseData = JSON.parse(response);

            if (responseData.error) {
              self.loading = false;
              self.woocommerce = false;
              return;
            }

            self.recentPosts = responseData.posts;
            self.maxPage = responseData.maxPages;
            self.totalFound = responseData.totalFound;
            self.loading = false;
            self.nonfound = responseData.nocontent;
          },
        });
      },
    },
    template:
      '<div style="padding: var(--a2020-card-padding);position:relative">\
    	  	<p v-if="totalFound == 0" class="uk-text-meta">{{nonfound}}</p>\
          <div v-if="!woocommerce" class="uk-alert-warning" uk-alert>\
              <p>{{translations.woocommerce}}</p>\
          </div>\
          <div v-if="woocommerce" class="uk-flex uk-width-auto uipress-background-wash uk-padding-small uk-border-rounded" >\
            <div class="uk-width-auto">\
                <div class="uk-h2 uk-margin-remove-bottom uk-text-bold uk-flex uk-flex-middle ">\
                  <span class="">{{totalFound}}</span>\
                </div>\
                <div class="uk-text-meta ">{{translations.inTheLast}} {{daysDif}} {{translations.days}}</div>\
            </div>\
          </div>\
          <loading-placeholder v-if="loading == true"></loading-placeholder>\
          <loading-placeholder v-if="loading == true"></loading-placeholder>\
          <loading-placeholder v-if="loading == true"></loading-placeholder>\
          <premium-overlay v-if="!premium" :translations="translations"></premium-overlay>\
    		  <table v-if="loading == false && formattedPosts.length > 0" class="uk-table uk-table-small uk-table-justify uk-table-middle">\
      			<tr class="" v-for="post in formattedPosts">\
              <td>\
      				<a :href="post.editURL" class="uk-link uk-text-bold uk-text-emphasis">{{post.title}}</a>\
      				<span class="uk-text-meta">{{post.date}}</span>\
      			  </td>\
              <td>\
                  <a :href="post.userURL">{{post.customer}}</a>\
              </td>\
              <td class="uk-text-right">\
                  <span class="a2020-post-label ">{{post.value}}</span>\
              </td>\
      			  <td class="uk-text-right">\
      				    <span class="a2020-post-label">{{post.status}}</span>\
      			  </td>\
      			</tr>\
    		  </table>\
    		  <div class="uk-flex" v-if="maxPage > 1">\
      		  <button @click="currentPage -= 1" :disabled="currentPage == 1"\
      		  class="uk-button uk-button-small uk-margin-small-right uk-flex uk-flex-middle" style="padding:5px 20px 5px 20px;"><span class="material-icons-outlined">chevron_left</span></button>\
      		  <button @click="currentPage += 1" :disabled="currentPage == maxPage"\
      		  class="uk-button uk-button-small uk-margin-right uk-flex uk-flex-middle"  style="padding:5px 20px 5px 20px;"><span class="material-icons-outlined">chevron_right</span></button>\
    		  </div>\
		 </div>',
  };
  return compData;
}

export default function () {
  console.log("Loaded");
}
