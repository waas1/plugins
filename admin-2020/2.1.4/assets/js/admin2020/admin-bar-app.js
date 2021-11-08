/////UPDATES
let a2020adminBarUpdates;
try {
  a2020adminBarUpdates = JSON.parse(admin2020_admin_bar_ajax.updates);
} catch (err) {
  a2020adminBarUpdates = [];
}

/// ADMIN NOTICES
let a2020adminBarNotices;
try {
  a2020adminBarNotices = admin2020_admin_bar_ajax.notices;
} catch (err) {
  a2020adminBarNotices = [];
}

/// SUPRESSED NOTICES
let a2020adminBarSupressed;
try {
  a2020adminBarSupressed = admin2020_admin_bar_ajax.supressed;
} catch (err) {
  a2020adminBarSupressed = [];
}

const a2020adminBarpt = JSON.parse(admin2020_admin_bar_ajax.postTypes);
const a2020adminBarPreferences = JSON.parse(admin2020_admin_bar_ajax.preferences);
const a2020adminBarMaster = JSON.parse(admin2020_admin_bar_ajax.master);

const a2020adminBar = {
  data() {
    return {
      loading: true,
      screenWidth: window.innerWidth,
      postTypes: a2020adminBarpt,
      updates: a2020adminBarUpdates,
      userMenu: {
        panel: "overview",
        offcanvas: false,
      },
      masterPrefs: {
        searchDisabled: a2020adminBarMaster.searchDisabled == "true",
        backend: a2020adminBarMaster.backend,
        create: a2020adminBarMaster.createEnabled == "true",
        viewHome: a2020adminBarMaster.homeEnabled == "true",
        legacyAdmin: a2020adminBarMaster.legacyAdmin == "true",
        notifcations: a2020adminBarMaster.notificationCenter == "true",
        showUpdates: a2020adminBarMaster.showUpdates == "true",
        showNotifications: a2020adminBarMaster.showNotifications == "true",
        hideEditProfile: a2020adminBarMaster.hideEditProfile == "true",
      },
      prefs: {
        legacyAdmin: a2020adminBarPreferences.legacyLinks == "true",
        screenOptions: a2020adminBarPreferences.screenOptions == "true",
        darkMode: a2020adminBarPreferences.darkmode == "true",
      },
      notifications: {
        total: 0,
        rawData: a2020adminBarNotices,
        data: [],
        supressed: a2020adminBarSupressed,
        supressedPage: 0,
      },
      search: {
        string: "",
        filters: [],
        results: [],
        perPage: 10,
        currentPage: 1,
        totalPages: 1,
        totalFound: 0,
        loading: false,
        categorized: [],
      },
    };
  },
  watch: {
    "notifications.supressed": function (newValue, oldValue) {
      if (newValue != oldValue) {
        a2020_save_user_prefences("a2020_supressed_notifications", newValue, false);
      }
    },
    "prefs.screenOptions": function (newValue, oldValue) {
      if (newValue != oldValue) {
        a2020_save_user_prefences("screen_options", newValue, false);
      }
    },
    "prefs.legacyAdmin": function (newValue, oldValue) {
      if (newValue != oldValue) {
        a2020_save_user_prefences("legacy_admin_links", newValue, false);
      }
    },
    "prefs.darkMode": function (newValue, oldValue) {
      if (newValue != oldValue) {
        a2020_save_user_prefences("darkmode", newValue, false);

        jQuery(".a2020_dark_anchor").toggleClass("uk-light");
        jQuery("body").toggleClass("a2020_night_mode");
      }
    },
  },
  created: function () {
    window.addEventListener("resize", this.getScreenWidth);
  },
  computed: {
    filteredSearch() {
      return this.search.results;
    },
    searchedCats() {
      return this.search.categorized;
    },

    allNotifications() {
      adminBar = this;
      data = jQuery.parseHTML(adminBar.notifications.rawData);
      adminBar.notifications.data = [];
      adminBar.notifications.supressedPage = 0;
      notis = adminBar.notifications.data;
      supressed = adminBar.notifications.supressed;

      jQuery(data).each(function () {
        temp = [];

        text = jQuery(this).text().trim().substring(0, 40);
        html = jQuery(this).prop("outerHTML");

        if (html) {
          if (!supressed.includes(text)) {
            temp["type"] = "primary";
            if (html.includes("notice-error")) {
              temp["type"] = "errormsg";
            }
            if (html.includes("notice-warning")) {
              temp["type"] = "warning";
            }
            if (html.includes("notice-success")) {
              temp["type"] = "success";
            }
            if (html.includes("notice-info")) {
              temp["type"] = "info";
            }

            temp["content"] = html;
            temp["shortDes"] = text;
            temp["open"] = false;
            notis.push(temp);
          } else {
            adminBar.notifications.supressedPage += 1;
          }
        }
      });
      adminBar.notifications.total = notis.length;
      return notis;
    },
  },
  mounted: function () {
    window.setInterval(() => {
      ///TIMED FUNCTIONS
    }, 15000);
    this.loading = false;
  },
  methods: {
    loadMoreResults() {
      perpage = this.search.perPage;
      this.search.perPage = Math.floor(perpage * 3);
      this.masterSearch();
    },
    listNumber(point) {
      perPage = this.search.perPage;
      currentPage = this.search.currentPage;
      foundItems = this.filteredSearch.length;

      startingPoint = perPage * currentPage - perPage + 1;
      endPoint = startingPoint + perPage - 1;

      if (endPoint > this.search.totalFound) {
        endPoint = this.search.totalFound;
      }

      if (point == "start") {
        return startingPoint;
      }

      if (point == "end") {
        return endPoint;
      }
    },
    getScreenWidth() {
      this.screenWidth = window.innerWidth;
    },
    isSmallScreen() {
      if (this.screenWidth < 1000) {
        return true;
      } else {
        return false;
      }
    },
    supressNotification(option, options) {
      notis = this.notifications.supressed;

      if (!Array.isArray(notis)) {
        this.notifications.supressed = [];
      }
      //test = [];
      //test.push(option);
      this.notifications.supressed.push(option);
      a2020_save_user_prefences("a2020_supressed_notifications", options, false);
    },

    nextSearchPage() {
      this.search.currentPage += 1;
      this.masterSearch();
    },
    previousSearchPage() {
      this.search.currentPage -= 1;
      this.masterSearch();
    },

    masterSearch() {
      adminbar = this;
      searchString = this.search.string;
      filters = this.search.filters;
      perpage = this.search.perPage;
      currentpage = this.search.currentPage;
      this.search.loading = true;

      jQuery.ajax({
        url: admin2020_admin_bar_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_master_search",
          security: admin2020_admin_bar_ajax.security,
          search: searchString,
          filters: filters,
          perpage: perpage,
          currentpage: currentpage,
        },
        success: function (response) {
          adminbar.search.loading = false;
          if (response) {
            data = JSON.parse(response);
            if (data.error) {
              UIkit.notification(data.error_message, "danger");
            } else {
              adminbar.search.results = data.founditems;
              adminbar.search.totalPages = data.totalpages;
              adminbar.search.totalFound = data.totalfound;
              adminbar.search.categorized = data.categorized;

              if (data.totalpages == 0) {
                return;
              }

              if (adminbar.search.currentPage > data.totalpages) {
                adminbar.search.currentPage = 1;
                adminbar.masterSearch();
              }
            }
          }
        },
      });
    },
  },
};

if (jQuery("#a2020-admin-bar-app").length > 0) {
  ///BUILD VUE APP
  const a2020adminBarApp = a2020Vue.createApp(a2020adminBar);

  a2020adminBarApp.mount("#a2020-admin-bar-app");
}
