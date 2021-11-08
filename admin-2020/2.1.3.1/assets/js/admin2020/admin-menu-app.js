const a2020menuObj = JSON.parse(a2020_menu_ajax.menu_object);
const a2020menuPrefs = JSON.parse(a2020_menu_ajax.preferences);
const a2020menuMaster = JSON.parse(a2020_menu_ajax.masterPrefs);

const a2020menu = {
  data() {
    return {
      loading: true,
      menu: a2020menuObj,
      search: "",
      screenWidth: window.innerWidth,
      menuPrefs: {
        icons: a2020menuPrefs.menuIcons == "true",
        subHover: a2020menuPrefs.subHover == "true",
        favourites: a2020menuPrefs.favourites,
        tempFavourites: [],
        favsOn: a2020menuPrefs.favsOn == "true",
        favsEditingMode: false,
        searchBar: a2020menuPrefs.menuSearch == "true",
        shrunk: a2020menuPrefs.menuShrunk == "true",
        darkMode: a2020menuPrefs.darkMode == "true",
        forceDarkMode: a2020menuPrefs.forcedarkMode == "true",
        forceLightMode: a2020menuPrefs.forcelightmode == "true",
      },
      master: {
        search: a2020menuMaster.search == "true",
        shrunkDefault: a2020menuMaster.collapseDefault == "true",
      },
    };
  },
  created: function () {
    window.addEventListener("resize", this.getScreenWidth);
  },
  computed: {
    filteredMenu() {
      searchString = this.search;
      menu = this.menu;
      searchedMenu = [];

      if (searchString == "") {
        for (i = 0; i < menu.length; i++) {
          if (menu[i].type == "sep") {
            continue;
          }
          theclasses = menu[i].classes;
          if (theclasses.includes("uk-open")) {
            menu[i].open = true;
          } else {
            menu[i].open = false;
          }
        }
        return menu;
      }

      searchString = searchString.toLowerCase();

      for (i = 0; i < menu.length; i++) {
        tempSub = [];
        name = menu[i].name;
        name = name.toLowerCase();
        menuObject = menu[i];

        if (menuObject.submenu) {
          for (p = 0; p < menuObject.submenu.length; p++) {
            name = menuObject.submenu[p].name;
            name = name.toLowerCase();

            if (name.includes(searchString)) {
              tempSub.push(menuObject.submenu[p]);
            }
          }
          if (tempSub.length > 0) {
            menuObject.submenu = tempSub;
            menuObject.open = true;
          }
        }
        name = menu[i].name;
        name = name.toLowerCase();

        if (name.includes(searchString) || tempSub.length > 0) {
          searchedMenu.push(menuObject);
        }
      }

      return searchedMenu;
    },

    favourites() {
      favourites = this.menuPrefs.favourites;
      menu = this.menu;
      theFavs = [];
      thesubs = [];

      for (i = 0; i < menu.length; i++) {
        if (menu[i].submenu) {
          for (p = 0; p < menu[i].submenu.length; p++) {
            if (favourites.includes(menu[i].submenu[p].id)) {
              theFavs.push(menu[i].submenu[p]);
            }
          }
        }
        if (favourites.includes(menu[i].id)) {
          theFavs.push(menu[i]);
        }
      }
      return theFavs;
    },
  },
  mounted: function () {
    window.setInterval(() => {
      ///TIMED FUNCTIONS
    }, 500);
    this.loading = false;
  },
  watch: {
    "menuPrefs.icons": function (newValue, oldValue) {
      if (newValue != oldValue) {
        a2020_save_user_prefences("a2020_menu_icons", newValue, false);
      }
    },
    "menuPrefs.favsOn": function (newValue, oldValue) {
      if (newValue != oldValue) {
        a2020_save_user_prefences("a2020_menu_favs_on", newValue, false);
      }
    },
    "menuPrefs.searchBar": function (newValue, oldValue) {
      if (newValue != oldValue) {
        a2020_save_user_prefences("a2020_menu_search", newValue, false);
      }
    },
    "menuPrefs.subHover": function (newValue, oldValue) {
      if (newValue != oldValue) {
        a2020_save_user_prefences("a2020_sub_hover", newValue, false);
      }
    },
    "menuPrefs.shrunk": function (newValue, oldValue) {
      if (newValue != oldValue) {
        a2020_save_user_prefences("a2020_menu_collapse", newValue, false);
      }
    },
  },
  methods: {
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
    //////TITLE: GETS APP CONTENT/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////DESCRIPTION: MAIN QUERY FOR CONTENT
    isIn(option, options) {
      return options.includes(option);
    },
    addFavourite(menuItem) {
      this.menuPrefs.favourites.push(menuItem.id);
      this.menuPrefs.tempFavourites.push(menuItem.id);
    },

    setFavourites() {
      this.menuPrefs.favsEditingMode = true;
    },

    cancelFavourites() {
      this.menuPrefs.favsEditingMode = false;
      addedItems = this.menuPrefs.tempFavourites;
      savedItems = this.menuPrefs.favourites;
      actual = [];

      for (i = 0; i < savedItems.length; i++) {
        if (!addedItems.includes(savedItems[i])) {
          actual.push(savedItems[i]);
        }
      }

      this.menuPrefs.favourites = actual;
      this.menuPrefs.tempFavourites = [];
    },

    clearFavourites() {
      this.menuPrefs.favourites = [];
    },

    saveFavourites() {
      this.menuPrefs.favsEditingMode = false;
      a2020_save_user_prefences("a2020_menu_favs", this.menuPrefs.favourites, true);
    },

    removeFavourite(menuItem) {
      const index = this.menuPrefs.favourites.indexOf(menuItem.id);
      if (index > -1) {
        this.menuPrefs.favourites.splice(index, 1);
      }
    },

    switchMenu() {
      this.menuPrefs.shrunk = !this.menuPrefs.shrunk;
      if (this.menuPrefs.shrunk) {
        jQuery("body").addClass("a2020_menu_small");
      } else {
        jQuery("body").removeClass("a2020_menu_small");
      }
    },
  },
};

///BUILD VUE APP
const a2020MenuApp = a2020Vue.createApp(a2020menu);

a2020MenuApp.mount("#a2020-menu-app");
