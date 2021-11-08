export function moduleName() {
  return "recent-posts";
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
        width: "uk-width-" + this.cardData.size,
        recentPosts: [],
        currentPage: 1,
        startDate: this.dateRange.startDate,
        maxPage: 1,
        totalFound: 0,
        loading: true,
        nonfound: "",
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
            action: "uipress_get_posts",
            security: uipress_overview_ajax.security,
            dates: self.getTheDates,
            currentPage: self.currentPage,
          },
          success: function (response) {
            var responseData = JSON.parse(response);

            if (responseData.error) {
              ///SOMETHING WENT WRONG
              UIkit.notification(responseData.error, { pos: "bottom-left", status: "danger" });
              self.loading = false;
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
      '<div style="padding: var(--a2020-card-padding);">\
	  	<p v-if="totalFound == 0" class="uk-text-meta">{{nonfound}}</p>\
      <div class="uk-flex   uk-width-auto uipress-background-wash uk-padding-small uk-border-rounded" >\
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
		  <table v-if="loading == false && formattedPosts.length > 0" class="uk-table uk-table-small uk-table-justify uk-table-middle">\
			<tr class="" v-for="post in formattedPosts">\
        <td>\
				<a :href="post.href" class="uk-link uk-text-bold uk-text-emphasis">{{post.title}}</a><br/>\
				<span class="uk-text-meta">{{post.author}}</span>\
			  </td>\
        <td>\
        <span class="a2020-post-label">{{post.type}}</span>\
        </td>\
			  <td class="uk-text-right">\
				{{post.date}}\
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
