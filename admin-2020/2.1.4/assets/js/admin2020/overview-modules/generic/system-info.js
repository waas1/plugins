export function moduleName() {
  return "system-info";
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
        recentPosts: [],
        currentPage: 1,
        maxPage: 1,
        totalFound: 0,
        loading: true,
      };
    },
    mounted: function () {
      this.loading = false;
    },
    computed: {
      getPostsOnce() {
        this.getPosts();
      },
      formattedPosts() {
        this.getPostsOnce;
        return this.recentPosts;
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
            action: "uipress_get_system_info",
            security: uipress_overview_ajax.security,
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
            self.loading = false;
          },
        });
      },
    },
    template:
      '<div style="padding: var(--a2020-card-padding);">\
	  	<loading-placeholder v-if="loading == true"></loading-placeholder>\
		  <loading-placeholder v-if="loading == true"></loading-placeholder>\
		  <table v-if="loading == false" class="uk-table uk-table-small uk-table-justify uk-margin-remove">\
			<tr class="" v-for="post in formattedPosts">\
			  <td>\
				{{post.name}}<br/>\
			  </td>\
			  <td class="uk-text-right">\
				  <span class="a2020-post-label">{{post.version}}</span>\
			  </td>\
			</tr>\
		  </table>\
		 </div>',
  };
  return compData;
}

export default function () {
  console.log("Loaded");
}
