export function moduleName() {
  return "popular-products";
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
        recentPosts: [],
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

        if (!this.recentPosts) {
          return [];
        } else {
          return this.recentPosts;
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
      getPosts() {
        let self = this;
        self.loading = true;

        jQuery.ajax({
          url: uipress_overview_ajax.ajax_url,
          type: "post",
          data: {
            action: "uipress_get_popular_products",
            security: uipress_overview_ajax.security,
            dates: self.getTheDates,
          },
          success: function (response) {
            var responseData = JSON.parse(response);

            if (responseData.error) {
              self.loading = false;
              self.woocommerce = false;
              return;
            }

            self.recentPosts = responseData.posts;
            self.loading = false;
            self.nonfound = responseData.nocontent;
            self.totalFound = responseData.totalFound;
          },
        });
      },
    },
    template:
      '<div class="" style="padding: var(--a2020-card-padding);position:relative">\
    	  	<p v-if="totalFound == 0" class="uk-text-meta">{{nonfound}}</p>\
          <div v-if="!woocommerce" class="uk-alert-warning" uk-alert>\
              <p>{{translations.woocommerce}}</p>\
          </div>\
          <loading-placeholder v-if="loading == true"></loading-placeholder>\
          <loading-placeholder v-if="loading == true"></loading-placeholder>\
          <loading-placeholder v-if="loading == true"></loading-placeholder>\
    		  <table v-if="loading == false && formattedPosts.length > 0" class="uk-table uk-table-small uk-table-justify uk-table-middle">\
            <thead>\
                <tr>\
                    <th>{{translations.product}}</th>\
                    <th>{{translations.sold}}</th>\
                    <th class="uk-text-right">{{translations.value}}</th>\
                </tr>\
            </thead>\
            <tbody>\
        			 <tr class="" v-for="post in formattedPosts">\
                  <td>\
                    <div class="uk-flex uk-flex-middle">\
                        <img v-if="post.img" :src="post.img" class="uk-margin-small-right" style="height:27px;width:27px;border-radius: 4px;">\
                        <span v-if="!post.img" class="material-icons-outlined uk-margin-small-right">local_offer</span>\
              				  <a :href="post.link" class="uk-link uk-text-bold uk-text-emphasis">{{post.title}}</a>\
                    </div>\
          			  </td>\
                  <td>\
                      {{post.salesCount}}\
                  </td>\
                  <td class="uk-text-right">\
                      <span class="a2020-post-label ">{{post.totalValue}}</span>\
                  </td>\
          			</tr>\
              </tbody>\
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
