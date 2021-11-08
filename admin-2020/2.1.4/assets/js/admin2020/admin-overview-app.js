const uipressOverviewSettings = JSON.parse(uipress_overview_ajax.options);
const uipressOverviewModules = JSON.parse(uipress_overview_ajax.modules);
const uipressOverviewTranslations = JSON.parse(uipress_overview_ajax.translations);

const uipressOverviewArgs = {
  data() {
    return {
      loading: true,
      screenWidth: window.innerWidth,
      settings: uipressOverviewSettings,
      modules: uipressOverviewModules,
      translations: uipressOverviewTranslations,
      ui: {
        editingMode: false,
      },
    };
  },
  created: function () {
    window.addEventListener("resize", this.getScreenWidth);
    var self = this;
  },
  computed: {
    originalMenu() {
      var originaltmen = this.master.menuItems;
      return originaltmen;
    },

    cardsWithIndex() {
      thecategories = this.settings.cards.formatted;
      newcats = [];

      ///LOOP CATEGORYS
      thecategories.forEach(function (category, i) {
        if (!category.columns) {
          category.columns = [];
        }
        thecolumns = category.columns;
        theCategoryIndex = i;
        tempColumns = [];
        category.id = theCategoryIndex;
        ///LOOP COLUMNS
        thecolumns.forEach(function (column, p) {
          thecards = column.cards;
          theColumnIndex = p;
          column.id = theCategoryIndex + "" + theColumnIndex;

          tempCards = [];

          if (!Array.isArray(thecards)) {
            thecards = [];
          }

          ///LOOP CARDS
          thecards.forEach(function (card, t) {
            theCardIndex = t;
            card.id = theCategoryIndex + "" + theColumnIndex + "" + theCardIndex + "" + card.name;
            tempCards.push(card);
          });

          column.cards = tempCards;
          tempColumns.push(column);
        });
        category.columns = tempColumns;
        newcats.push(category);
      });

      return newcats;
    },
  },
  mounted: function () {
    this.loading = false;
  },
  methods: {
    exportCards() {
      self = this;
      ALLoptions = JSON.stringify(self.settings.cards.formatted);

      var today = new Date();
      var dd = String(today.getDate()).padStart(2, "0");
      var mm = String(today.getMonth() + 1).padStart(2, "0"); //January is 0!
      var yyyy = today.getFullYear();

      date_today = mm + "_" + dd + "_" + yyyy;
      filename = "uipress_dash_" + date_today + ".json";

      var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(ALLoptions);
      var dlAnchorElem = document.getElementById("uip_export_dash");
      dlAnchorElem.setAttribute("href", dataStr);
      dlAnchorElem.setAttribute("download", filename);
      dlAnchorElem.click();
    },
    importCards() {
      self = this;
      allTranslations = self.translations;

      var thefile = jQuery("#uipress_import_cards")[0].files[0];

      if (thefile.type != "application/json") {
        window.alert(allTranslations.validJSON);
        return;
      }

      if (thefile.size > 100000) {
        window.alert(allTranslations.fileBig);
        return;
      }

      var file = document.getElementById("uipress_import_cards").files[0];
      var reader = new FileReader();
      reader.readAsText(file, "UTF-8");

      reader.onload = function (evt) {
        json_settings = evt.target.result;
        parsed = JSON.parse(json_settings);

        if (parsed != null) {
          parsed.id = null;
          ///GOOD TO GO;
          self.settings.cards.formatted = parsed;
          UIkit.notification(allTranslations.layoutImported, { pos: "bottom-left", status: "success" });
          self.saveDash();
        } else {
          UIkit.notification(allTranslations.layoutExportedProblem, { pos: "bottom-left", status: "danger" });
        }
      };
    },
    isSmallScreen() {
      if (this.screenWidth < 1000) {
        return true;
      } else {
        return false;
      }
    },
    analyticsAcountConnected() {
      this.settings.analyticsAccount = true;
    },
    logDrop() {
      this.cardsWithIndex;
    },
    removeGoogleAccount() {
      self = this;

      jQuery.ajax({
        url: uipress_overview_ajax.ajax_url,
        type: "post",
        data: {
          action: "uipress_remove_google_account",
          security: uipress_overview_ajax.security,
        },
        success: function (response) {
          data = JSON.parse(response);

          if (data.error) {
            ///SOMETHING WENT WRONG
            UIkit.notification(data.error, { pos: "bottom-left", status: "danger" });
            return;
          }

          self.settings.analyticsAccount = false;
          UIkit.notification(data.message, { pos: "bottom-left", status: "primary" });
        },
      });
    },
    resetOverview() {
      self = this;

      UIkit.modal.confirm(self.translations.confirmReset).then(
        function () {
          self.forceReset();
        },
        function () {}
      );
    },
    forceReset() {
      self = this;

      jQuery.ajax({
        url: uipress_overview_ajax.ajax_url,
        type: "post",
        data: {
          action: "uipress_reset_overview",
          security: uipress_overview_ajax.security,
        },
        success: function (response) {
          data = JSON.parse(response);

          if (data.error) {
            ///SOMETHING WENT WRONG
            UIkit.notification(data.error, { pos: "bottom-left", status: "danger" });
            return;
          }
          location.reload();
          UIkit.notification(data.message, { pos: "bottom-left", status: "primary" });
        },
      });
    },
    saveDash() {
      self = this;

      jQuery.ajax({
        url: uipress_overview_ajax.ajax_url,
        type: "post",
        data: {
          action: "uipress_save_dash",
          security: uipress_overview_ajax.security,
          cards: self.settings.cards.formatted,
        },
        success: function (response) {
          data = JSON.parse(response);

          if (data.error) {
            ///SOMETHING WENT WRONG
            UIkit.notification(data.error, { pos: "bottom-left", status: "danger" });
            return;
          }

          UIkit.notification(data.message, { pos: "bottom-left", status: "primary" });
        },
      });
    },
    getMenus() {
      self = this;

      jQuery.ajax({
        url: uipress_overview_ajax.ajax_url,
        type: "post",
        data: {
          action: "uipress_get_menus",
          security: uipress_overview_ajax.security,
        },
        success: function (response) {
          data = JSON.parse(response);

          if (data.error) {
            ///SOMETHING WENT WRONG
            UIkit.notification(data.error, { pos: "bottom-left", status: "danger" });
            return;
          }

          self.user.allMenus = data.menus;
        },
      });
    },
    setCardIndex(option, index) {
      option.index = index;
      return index;
    },
    setDragData() {
      return {
        class: "uk-grid uk-grid-masonry uip-card-area",
        //"uk-grid": "masonry: true",
      };
    },
    moveColumnUp(index) {
      arr = this.settings.cards.formatted;
      new_index = index - 1;
      arr.splice(new_index, 0, arr.splice(index, 1)[0]);
    },
    moveColumnDown(index) {
      arr = this.settings.cards.formatted;
      new_index = index + 1;
      arr.splice(new_index, 0, arr.splice(index, 1)[0]);
    },

    addNewColumn(theColumn) {
      theColumn.push({ size: "small", cards: [] });
      UIkit.notification(this.translations.colAdded, { pos: "bottom-left", status: "primary" });
    },
    newSection() {
      this.settings.cards.formatted.push({ name: "Section name", desc: "Section description", open: true, columns: [] });
      UIkit.notification(this.translations.sectionAdded, { pos: "bottom-left", status: "primary" });
    },
    deleteSection(index) {
      this.settings.cards.formatted.splice(index, 1);
    },
    removeCard(theParent, index) {
      theParent.cards.splice(index, 1);
    },
    removeCol(theParent, index) {
      theParent.splice(index, 1);
    },
    getdatafromComp(data) {
      return data;
    },
  },
};

const uipressOverviewApp = Vue.createApp(uipressOverviewArgs);

uipressOverviewApp.component("date-range-picker", {
  props: {
    dates: Object,
  },
  data: function () {
    return {
      date: {
        startDate: this.dates.startDate,
        endDate: this.dates.endDate,
      },
    };
  },
  mounted: function () {
    let datepicker = this;

    const picker = new Litepicker({
      element: document.getElementById("uip-date-range"),
      singleMode: false,
      plugins: ["ranges"],
      numberOfColumns: 2,
      numberOfMonths: 2,
      startDate: datepicker.date.startDate,
      endDate: datepicker.date.endDate,
      format: "DD MMM, YYYY",
    });

    picker.on("selected", (date1, date2) => {
      // some action
      thedates = {
        startDate: picker.getStartDate().format("YYYY-MM-DD"),
        endDate: picker.getEndDate().format("YYYY-MM-DD"),
      };
      datepicker.returnNewDates(thedates);
    });
  },
  methods: {
    returnNewDates(dateObj) {
      this.$emit("date-change", dateObj);
    },
  },
  template:
    '<div class="uk-flex uk-flex uk-flex-center uk-flex-middle uip-date-range-container">\
      <span class="material-icons-outlined  uk-text-muted" style="margin-right:5px;">date_range</span>\
      <input class="uk-input uk-form-small" type="text" id="uip-date-range" readonly>\
      <span class="material-icons-outlined  uk-text-muted" style="margin-left:5px;">expand_more</span>\
    </div>',
});
uipressOverviewApp.component("loading-placeholder", {
  data: function () {
    return {};
  },
  methods: {
    doStuff() {},
  },
  template:
    '<svg class="uk-margin " role="img" width="340" height="84" aria-labelledby="loading-aria" viewBox="0 0 340 84" preserveAspectRatio="none">\
    <title id="loading-aria">Loading...</title>\
    <rect x="0" y="0" width="100%" height="100%" clip-path="url(#clip-path)" style=\'fill: url("#fill");\'></rect>\
    <defs>\
      <clipPath id="clip-path">\
        <rect x="0" y="0" rx="3" ry="3" width="67" height="11" />\
        <rect x="76" y="0" rx="3" ry="3" width="140" height="11" />\
        <rect x="127" y="48" rx="3" ry="3" width="53" height="11" />\
        <rect x="187" y="48" rx="3" ry="3" width="72" height="11" />\
        <rect x="18" y="48" rx="3" ry="3" width="100" height="11" />\
        <rect x="0" y="71" rx="3" ry="3" width="37" height="11" />\
        <rect x="18" y="23" rx="3" ry="3" width="140" height="11" />\
        <rect x="166" y="23" rx="3" ry="3" width="173" height="11" />\
      </clipPath>\
      <linearGradient id="fill">\
        <stop offset="0.599964" stop-color="rgba(156, 155, 155, 13%)" stop-opacity="1">\
          <animate attributeName="offset" values="-2; -2; 1" keyTimes="0; 0.25; 1" dur="2s" repeatCount="indefinite"></animate>\
        </stop>\
        <stop offset="1.59996" stop-color="rgba(156, 155, 155, 20%)" stop-opacity="1">\
          <animate attributeName="offset" values="-1; -1; 2" keyTimes="0; 0.25; 1" dur="2s" repeatCount="indefinite"></animate>\
        </stop>\
        <stop offset="2.59996" stop-color="rgba(156, 155, 155, 13%)" stop-opacity="1">\
          <animate attributeName="offset" values="0; 0; 3" keyTimes="0; 0.25; 1" dur="2s" repeatCount="indefinite"></animate>\
        </stop>\
      </linearGradient>\
    </defs>\
  </svg>',
});

uipressOverviewApp.component("connect-google-analytics", {
  emits: ["account-connected"],
  props: {
    translations: Object,
  },
  data: function () {
    return {
      imgloading: false,
      googliconNoHover: "",
      googliconHover: "",
    };
  },
  mounted: function () {
    this.startBuild;
  },
  computed: {
    returnHoverImg() {
      return this.googliconHover;
    },
    returnNoHoverImg() {
      return this.googliconNoHover;
    },
    isLoading() {
      return this.imgloading;
    },
    startBuild() {
      this.getImages();
    },
  },
  methods: {
    gauthWindow() {
      let self = this;
      var url =
        "https://accounts.google.com/o/oauth2/auth?response_type=code&client_id=583702447211-6qiibg31fdkiug7r41qobqi1c1js1jps.apps.googleusercontent.com&redirect_uri=https://admintwentytwenty.com/analytics/view.php&scope=https://www.googleapis.com/auth/analytics.readonly&access_type=offline&approval_prompt=force";
      var newWindow = window.open(url, "name", "height=600,width=450");

      if (window.focus) {
        newWindow.focus();
      }

      window.onmessage = function (e) {
        if (e.origin == "https://admintwentytwenty.com" && e.data) {
          try {
            var analyticsdata = JSON.parse(e.data);

            if (analyticsdata.code && analyticsdata.view) {
              newWindow.close();
              self.uip_save_analytics(analyticsdata.view, analyticsdata.code);
            }
          } catch (err) {
            ///ERROR
          }
        }
      };
    },
    uip_save_analytics(view, code) {
      let self = this;
      jQuery.ajax({
        url: uipress_overview_ajax.ajax_url,
        type: "post",
        data: {
          action: "uipress_save_analytics_account",
          security: uipress_overview_ajax.security,
          view: view,
          code: code,
        },
        success: function (response) {
          data = JSON.parse(response);

          if (data.error) {
            ///SOMETHING WENT WRONG
            UIkit.notification(data.error, { pos: "bottom-left", status: "danger" });
            self.loading = false;
            return;
          }

          //picker = jQuery("#uip-date-range");
          //picker.setDateRange(moment().subtract(5, "days"), moment());
          console.log(self.$root);
          self.$root.analyticsAcountConnected();
          //this.$root.$emit("account-connected");
          UIkit.notification(data.message, { pos: "bottom-left", status: "success" });
          self.loading = false;
        },
      });
    },
    getImages() {
      let self = this;
      self.loading = true;

      jQuery.ajax({
        url: uipress_overview_ajax.ajax_url,
        type: "post",
        data: {
          action: "uipress_get_google_images",
          security: uipress_overview_ajax.security,
        },
        success: function (response) {
          data = JSON.parse(response);

          if (data.error) {
            ///SOMETHING WENT WRONG
            UIkit.notification(data.error, { pos: "bottom-left", status: "danger" });
            self.loading = false;
            return;
          }

          self.loading = false;

          self.googliconNoHover = data.googliconNoHover;
          self.googliconHover = data.googliconHover;
        },
      });
    },
  },
  template:
    '<p>{{translations.noaccount}}</p>\
    <loading-placeholder v-if="isLoading == true"></loading-placeholder>\
    <a v-if="!isLoading" class="admin2020_google_sign_in" href="#" @click="gauthWindow()">\
        <img class="admin2020_icon_no_hover" width="191" :src="returnNoHoverImg">\
        <img class="admin2020_icon_hover" width="191" :src="returnHoverImg">\
    </a>',
});

uipressOverviewApp.component("card-options", {
  emits: ["remove-card", "card-change"],
  props: {
    translations: Object,
    card: Object,
    cardindex: Number,
  },
  data: function () {
    return {
      theCard: this.card,
      theIndex: this.cardindex,
      theID: this.card.id,
      theCardName: this.card.name,
    };
  },
  computed: {
    returnIndex() {
      return this.theIndex;
    },
    returnCard() {
      let self = this;
      return self.theCard;
    },
  },
  mounted: function () {
    datepicker = this;
  },
  methods: {
    removeCard() {
      this.$emit("remove-card");
    },
  },
  watch: {
    theCard: function (newValue, oldValue) {
      let self = this;
      this.$emit("card-change", self.returnCard);
    },
  },
  template:
    '<a :class="{\'uk-light\' : returnCard.lightDark && returnCard.lightDark != \'false\'}"\
    href="#"><span class="material-icons-outlined" @click="returnCard.optionsOpen = !returnCard.optionsOpen">more_horiz</span></a>\
    <div  uk-dropdown="mode:click;pos:bottom-right">\
      <ul class="uk-nav uk-dropdown-nav">\
        <li>\
        <div class="uk-dark">{{translations.cardWidth}}</div>\
          <select class="uk-select uk-form-small uk-margin-small" v-model="returnCard.size">\
              <option value="xxsmall">{{translations.xxsmall}}</option>\
              <option value="xsmall">{{translations.xsmall}}</option>\
              <option value="small">{{translations.small}}</option>\
              <option value="small-medium">{{translations.smallmedium}}</option>\
              <option value="medium">{{translations.medium}}</option>\
              <option value="medium-large">{{translations.mediumlarge}}</option>\
              <option value="large">{{translations.large}}</option>\
              <option value="xlarge">{{translations.xlarge}}</option>\
          </select>\
        </li>\
        <li class="uk-margin">\
            <div class="uk-margin-small-bottom">{{translations.bgcolor}}</div>\
            <label class="a2020-color-picker"\
            v-bind:style="{\'background-color\' : returnCard.bgColor}">\
              <input class="" type="color" v-model="returnCard.bgColor" style="visibility: hidden;">\
            </label>\
            <input class="a2020-white-input" \
            type="text"\
            :placeholder="translations.colorPlace"\
            v-model="returnCard.bgColor" style="padding-left:50px;width:150px;">\
        </li>\
        <li class="uk-margin">\
          <div class="">{{translations.lightText}}</div>\
          <label class="admin2020_switch uk-margin-small-top">\
          <input type="checkbox" v-model="returnCard.lightDark">\
            <span class="admin2020_slider "></span>\
          </label>\
        </li>\
        <li class="uk-margin">\
          <div class="">{{translations.removeBackground}}</div>\
          <label class="admin2020_switch uk-margin-small-top">\
          <input type="checkbox" v-model="returnCard.nobg">\
            <span class="admin2020_slider "></span>\
          </label>\
      </li>\
        <li><button @click="removeCard()" class="uk-button uk-button-small uk-button-danger ">{{translations.remove}}</button></li>\
      </ul>\
  </div>',
});

const highlight = (editor) => {
  editor.textContent = editor.textContent;
  hljs.highlightBlock(editor);
};

let editorOptions = {
  tab: " ".repeat(2), // default is \t
};

uipressOverviewApp.component("code-flask", {
  data: function () {
    return {
      created: false,
      unformatted: this.usercode,
    };
  },
  props: {
    language: String,
    usercode: String,
  },
  computed: {
    returnCode() {
      return this.unformatted;
    },
  },
  mounted: function () {
    this.testel();
  },
  methods: {
    codeChange(thecode) {
      this.$emit("code-change", thecode);
      //self.usercode = code;
    },
    //////TITLE: ADDS A SLECTED OPTION//////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////DESCRIPTION: ADDS A SELECTED OPTION FROM OPTIONS
    testel() {
      self = this;
      const editor = this.$el;
      const jar = new CodeJar(editor, highlight, editorOptions);

      jar.onUpdate((code) => {
        this.codeChange(code);
      });
    },
  },
  template: '<div class="editor " :class="language"  data-gramm="false">{{returnCode}}</div> ',
});

uipressOverviewApp.component("premium-overlay", {
  props: {
    translations: Object,
  },
  data: function () {
    return {};
  },
  methods: {},
  template:
    '<div style="top: -42px !important;\
  left: 0 !important;\
  position: absolute;\
  right: 0 !important;\
  bottom: 0 !important;\
  background-color:#f0f8ff33;\
  display: block !important;\
  backdrop-filter: blur(4px) !important;\
  opacity: 1 !important;\
  z-index: 9;\
  visibility: visible !important;" >\
    <div class="uk-flex uk-flex-middle uk-flex-center uk-height-1-1">\
      <div class="uk-width-2-3 uk-flex uk-flex-column uk-flex-center">\
            <div class="uk-h4 uk-text-bold uk-text-center">\
              {{translations.upgradMsg}}\
            </div>\
            <a href="https://uipress.co/pricing" target="_BLANK"  class="uk-button uk-button-small uk-button-danger uk-flex uk-flex-middle uk-flex-center" style="background-color:#32d296 !important;align-self: center;">\
            <span class="material-icons-outlined uk-margin-small-right" style="font-size:20px;">redeem</span>\
            {{translations.premium}}\
          </a>\
      </div>\
    </div>\
  </div>',
});

uipressOverviewApp.component("col-editer", {
  props: {
    translations: Object,
    column: Object,
    modules: Object,
    premium: Boolean,
  },
  data: function () {
    return {
      theColumn: this.column,
    };
  },
  mounted: function () {},
  methods: {
    removeCol() {
      this.$emit("remove-col");
    },
    getdatafromComp(column) {
      this.$emit("col-change", column);
    },
    columnUpdated(column) {
      this.$emit("col-change", column);
    },
  },
  watch: {
    theCard: function (newValue, oldValue) {
      tempCard = this.theColumn;
      this.$emit("col-change", tempCard);
    },
  },
  template:
    '<div class="uk-width-1-1 uk-margin-bottom">\
      <div class="uk-background-default uk-padding-small uip-column-editing" >\
      <div class="uk-text-bold uk-margin-small">{{translations.columnSettings}}</div>\
        <div class="uk-grid uk-grid-small">\
          <div class="uk-width-expand">\
            <select class="uk-select uk-form-small uk-margin-small" v-model="theColumn.size">\
                <option value="xxsmall">{{translations.xxsmall}}</option>\
                <option value="xsmall">{{translations.xsmall}}</option>\
                <option value="small">{{translations.small}}</option>\
                <option value="small-medium">{{translations.smallmedium}}</option>\
                <option value="medium">{{translations.medium}}</option>\
                <option value="medium-large">{{translations.mediumlarge}}</option>\
                <option value="large">{{translations.large}}</option>\
                <option value="xlarge">{{translations.xlarge}}</option>\
            </select>\
          </div>\
          <div class="uk-width-auto uk-flex uk-flex-bottom uk-flex-right">\
            <button  class="uk-button uk-button-primary uk-button-small">{{translations.addCard}}</button>\
          </div>\
          <card-selector :premium="premium" @card-added="columnUpdated($event)" :theColumn="theColumn" :translations="translations" :modules="modules"></card-selector>\
          <div class="uk-width-auto uk-flex uk-flex-bottom uk-flex-right">\
            <button @click="removeCol()" class="uk-button uk-button-danger uk-button-small">{{translations.deleteCol}}</button>\
          </div>\
        </div>\
      </div>\
    </div>',
});

uipressOverviewApp.component("card-selector", {
  props: {
    translations: Object,
    theColumn: Object,
    modules: Object,
    premium: Boolean,
  },
  data: function () {
    return {
      theCol: this.theColumn,
      searchString: "",
    };
  },
  mounted: function () {
    datepicker = this;
  },
  methods: {
    addCard(card) {
      let self = this;

      if (!self.theCol.cards) {
        self.theCol.cards = [];
      }
      self.theCol.cards.push({ name: card.name, compName: card.moduleName, size: "xlarge" });
      this.$emit("card-added", self.theCol);
      UIkit.notification(self.translations.cardAdded, { pos: "bottom-left", status: "primary" });
      //this.$emit("remove-col");
    },
  },
  template:
    '<div uk-dropdown="mode: click;pos:bottom-center" class="uk-dropdown  uk-dropdown-bottom-center" >\
    <div class="">\
        <input class="uk-input uk-input-small uk-margin-bottom" v-model="searchString" :placeholder="translations.searchCards" style="width:500px;">\
        <div class="uk-grid uk-grid-small" style="width:500px;max-height:500px;overflow:auto;">\
        <template v-for="module in modules" >\
          <div v-if="module.name.includes(searchString) || module.description.includes(searchString)" class="uk-width-1-2">\
            <div class="uk-border-rounded a2020-border all uk-padding-small uk-margin-small-bottom">\
              <div class="uk-h4 uk-margin-top-small">{{module.name}}</div>\
              <span class="a2020-post-label uk-text-bold uk-margin-small-bottom">{{module.category}}</span>\
              <div class="uk-text-meta uk-margin">{{module.description}}</div>\
              <button @click="addCard(module)" v-if="module.premium && premium == true" class="uk-button uk-button-small uk-button-secondary">{{translations.addCard}}</button>\
              <button @click="addCard(module)" v-if="!module.premium" class="uk-button uk-button-small uk-button-secondary">{{translations.addCard}}</button>\
              <a href="https://uipress.co/pricing" target="_BLANK" v-if="module.premium && premium == false" class="uk-button uk-button-small uk-button-danger uk-flex uk-flex-middle uk-flex-center">\
                <span class="material-icons-outlined uk-margin-small-right" style="font-size:20px;">redeem</span>\
                {{translations.premium}}\
              </a>\
            </div>\
          </div>\
          </template>\
        </div>\
    </div>\
  </div>',
});

uipressOverviewApp.component("uip-chart", {
  props: {
    type: String,
    gridLines: Boolean,
    chartData: Object,
    dates: Object,
    colours: {
      bgColors: [],
      borderColors: [],
    },
    cWidth: String,
    borderWidth: Number,
    cutout: String,
    spacing: Number,
    borderradius: Number,
    removeLabels: Boolean,
  },
  data: function () {
    return {
      theCard: this.card,
      theDates: this.dates,
      defaultColors: {
        bgColors: ["rgba(255, 99, 132, 0.2)", "rgba(54, 162, 235, 0.2)", "rgba(255, 206, 86, 0.2)", "rgba(75, 192, 192, 0.2)", "rgba(153, 102, 255, 0.2)", "rgba(255, 159, 64, 0.2)"],
        borderColors: ["rgba(255, 99, 132, 1)", "rgba(54, 162, 235, 1)", "rgba(255, 206, 86, 1)", "rgba(75, 192, 192, 1)", "rgba(153, 102, 255, 1)", "rgba(255, 159, 64, 1)"],
      },
    };
  },
  mounted: function () {
    theChart = this;
    this.renderChart();
  },
  computed: {
    bgColors() {
      if (this.backgroundColors) {
        return this.backgroundColors;
      } else {
        return this.defaultColors;
      }
    },
    chartWidth() {
      if (this.cWidth) {
        return this.cWidth;
      } else {
        return "100%";
      }
    },
    displayLabels() {
      if (this.removeLabels == true) {
        return false;
      } else {
        return true;
      }
    },
  },
  methods: {
    getTooltip(context) {
      // Tooltip Element
      var tooltipEl = document.getElementById("chartjs-tooltip");

      // Create element on first render
      if (!tooltipEl) {
        tooltipEl = document.createElement("div");
        tooltipEl.id = "chartjs-tooltip";
        tooltipEl.innerHTML = "<div class='uk-background-default a2020-border all uk-padding-small uk-box-shadow-small uk-border-rounded uip-tolltip'></div>";
        document.body.appendChild(tooltipEl);
      }

      // Hide if no tooltip
      var tooltipModel = context.tooltip;
      if (tooltipModel.opacity === 0) {
        tooltipEl.style.opacity = 0;
        return;
      }

      // Set caret Position
      tooltipEl.classList.remove("above", "below", "no-transform");
      if (tooltipModel.yAlign) {
        tooltipEl.classList.add(tooltipModel.yAlign);
      } else {
        tooltipEl.classList.add("no-transform");
      }

      function getBody(bodyItem) {
        return bodyItem.lines;
      }

      // Set Text
      if (tooltipModel.body) {
        var titleLines = tooltipModel.title || [];
        var bodyLines = tooltipModel.body.map(getBody);

        var innerHtml = "";

        titleLines.forEach(function (title) {
          innerHtml += "<div class='uk-h5 uk-text uk-text-default uk-margin-small-bottom '>" + title + "</div>";
        });

        bodyData = tooltipModel.dataPoints;

        bodyData.forEach(function (body, i) {
          datasetLabel = body.label;
          datasetValue = body.formattedValue;

          innerHtml += '<div style="margin-bottom:5px;">';
          var colors = tooltipModel.labelColors[i];
          var style = "background:" + colors.backgroundColor;
          style += "; border: 2px solid " + colors.borderColor;
          style += "; border-radius: 50%";
          style += "; width: 7px";
          style += "; height: 7px";
          style += "; display: inline-block";
          style += "; margin-right: 5px";
          innerHtml += '<span style="' + style + '"></span>';

          textStyle = "color: " + colors.borderColor + ";margin-right:5px;";
          innerHtml += '<span class="uk-h5 uk-text-small" style="' + textStyle + '">' + datasetValue + "</span>";
          innerHtml += '<span class="uk-h5 uk-text-small uk-text-muted">' + body.dataset.label + "</span>";
          innerHtml += "</div>";
        });
        innerHtml += "";

        var tableRoot = tooltipEl.querySelector("div");
        tableRoot.innerHTML = innerHtml;
      }

      var position = context.chart.canvas.getBoundingClientRect();
      var bodyFont = Chart.helpers.toFont(tooltipModel.options.bodyFont);

      // Display, position, and set styles for font
      tooltipEl.style.opacity = 1;
      tooltipEl.style.position = "absolute";
      tooltipEl.style.left = position.left + window.pageXOffset + tooltipModel.caretX + "px";
      tooltipEl.style.top = position.top + window.pageYOffset + tooltipModel.caretY + "px";
      tooltipEl.style.font = bodyFont.string;
      tooltipEl.style.padding = tooltipModel.padding + "px " + tooltipModel.padding + "px";
      tooltipEl.style.pointerEvents = "none";
    },
    getOptions() {
      let self = this;
      let chartOptions = [];

      if (this.type == "doughnut") {
        options = {
          borderWidth: 0,
          cutout: "80%",
          spacing: 0,
          borderRadius: 0,
          layout: {
            padding: 0,
          },
          plugins: {
            legend: {
              display: self.displayLabels,
              position: "bottom",
              align: "left",
              labels: {
                padding: 10,
                usePointStyle: true,
              },
            },
            tooltip: {
              enabled: false,
              external: function (context) {
                self.getTooltip(context);
              },
            },
          },
          scales: {
            x: {
              ticks: {
                display: theChart.gridLines,
              },
              grid: {
                borderWidth: 0,
                display: theChart.gridLines,
              },
            },
            y: {
              beginAtZero: true,
              ticks: {
                display: theChart.gridLines,
              },
              grid: {
                borderWidth: 0,
                display: theChart.gridLines,
                border: theChart.gridLines,
              },
            },
          },
        };

        chartOptions = options;
      } else {
        options = {
          cutout: "0%",
          spacing: 0,
          borderRadius: 0,
          tension: 0.1,
          pointRadius: 0,
          pointHoverRadius: 5,
          borderRadius: 4,
          interaction: {
            mode: "nearest",
          },
          hover: {
            intersect: false,
          },
          borderSkipped: false,
          plugins: {
            legend: {
              display: self.displayLabels,
              position: "bottom",
              align: "start",
              padding: 10,
              labels: {
                padding: 10,
                usePointStyle: true,
                pointStyle: "rectRounded",
              },
              title: {
                padding: 0,
                display: true,
              },
            },
            tooltip: {
              position: "average",
              backgroundColor: "#fff",
              padding: 20,
              bodySpacing: 10,
              bodyFont: {
                size: 12,
              },
              titleFont: {
                size: 14,
                weight: "bold",
              },
              mode: "index",
              intersect: false,
              xAlign: "left",
              yAlign: "center",
              caretPadding: 10,
              cornerRadius: 4,
              borderColor: "rgba(162, 162, 162, 0.2)",
              borderWidth: 1,
              titleColor: "#333",
              bodyColor: "#777",
              titleMarginBottom: 10,
              bodyFontSize: 100,
              usePointStyle: true,

              enabled: false,

              external: function (context) {
                self.getTooltip(context);
              },
            },
          },
          scales: {
            x: {
              ticks: {
                display: false,
              },
              grid: {
                borderWidth: 1,
                display: true,
                borderDash: [10, 8],
                color: "rgba(162, 162, 162, 0.4)",
              },
            },
            y: {
              beginAtZero: true,
              ticks: {
                display: false,
              },
              grid: {
                borderWidth: 0,
                display: false,
              },
            },
          },
        };

        chartOptions = options;
      }

      if (self.type == "horizontalbar") {
        chartOptions.indexAxis = "y";
      }

      return chartOptions;
    },
    renderChart() {
      let theChart = this;
      let temptype = theChart.type;

      if (theChart.type == "horizontalbar") {
        theChart.chartData.datasets;
        temptype = "bar";
        let newdata = [];

        theChart.chartData.datasets.forEach(function (body, i) {
          body.axis = "y";
          newdata.push(body);
        });

        theChart.chartData.datasets = newdata;
      }

      var ctx = this.$el.getContext("2d");
      var myChart = new Chart(ctx, {
        type: temptype,
        data: theChart.chartData,
        options: theChart.getOptions(),
      });
    },
  },
  template: '<canvas :width="chartWidth" height="200" :dat-sd="dates.startDate" :dat-sed="dates.endDate"></canvas>',
});

uipressOverviewApp.component("uip-country-chart", {
  props: {
    type: String,
    cdata: Object,
    dates: Object,
    translations: Object,
  },
  data: function () {
    return {
      theCard: this.card,
      theDates: this.dates,
    };
  },
  mounted: function () {
    theChart = this;
    this.renderChart();
  },
  methods: {
    getTooltip(context) {
      // Tooltip Element
      var tooltipEl = document.getElementById("chartjs-tooltip");

      // Create element on first render
      if (!tooltipEl) {
        tooltipEl = document.createElement("div");
        tooltipEl.id = "chartjs-tooltip";
        tooltipEl.innerHTML = "<div class='uk-background-default a2020-border all uk-padding-small uk-box-shadow-small uk-border-rounded uip-tolltip'></div>";
        document.body.appendChild(tooltipEl);
      }

      // Hide if no tooltip
      var tooltipModel = context.tooltip;
      if (tooltipModel.opacity === 0) {
        tooltipEl.style.opacity = 0;
        return;
      }

      // Set caret Position
      tooltipEl.classList.remove("above", "below", "no-transform");
      if (tooltipModel.yAlign) {
        tooltipEl.classList.add(tooltipModel.yAlign);
      } else {
        tooltipEl.classList.add("no-transform");
      }

      function getBody(bodyItem) {
        return bodyItem.lines;
      }

      // Set Text
      if (tooltipModel.body) {
        var titleLines = tooltipModel.title || [];
        var bodyLines = tooltipModel.body.map(getBody);

        var innerHtml = "";

        bodyData = tooltipModel.dataPoints;

        parts = bodyLines[0][0].split(":");
        splittitle = parts[0];

        innerHtml += "<div class='uk-h5 uk-text uk-text-default ' style='margin-bottom:5px;'>" + splittitle + "</div>";

        bodyData.forEach(function (body, i) {
          datasetLabel = body.label;
          datasetValue = body.formattedValue;

          innerHtml += '<div style="margin-bottom:5px;">';
          var colors = tooltipModel.labelColors[i];
          var style = "background:" + "rgba(12, 92, 239, 0.05)";
          style += "; border: 2px solid " + "rgba(12, 92, 239, 1)";
          style += "; border-radius: 50%";
          style += "; width: 7px";
          style += "; height: 7px";
          style += "; display: inline-block";
          style += "; margin-right: 5px";
          innerHtml += '<span style="' + style + '"></span>';

          textStyle = "color: " + "rgba(12, 92, 239, 1)" + ";margin-right:5px;";
          innerHtml += '<span class="uk-h5 uk-text-small" style="' + textStyle + '">' + datasetValue + "</span>";
          innerHtml += '<span class="uk-h5 uk-text-small uk-text-muted">' + body.dataset.label + "</span>";
          innerHtml += "</div>";
        });
        innerHtml += "";

        var tableRoot = tooltipEl.querySelector("div");
        tableRoot.innerHTML = innerHtml;
      }

      var position = context.chart.canvas.getBoundingClientRect();
      var bodyFont = Chart.helpers.toFont(tooltipModel.options.bodyFont);

      // Display, position, and set styles for font
      tooltipEl.style.opacity = 1;
      tooltipEl.style.position = "absolute";
      tooltipEl.style.left = position.left + window.pageXOffset + tooltipModel.caretX + "px";
      tooltipEl.style.top = position.top + window.pageYOffset + tooltipModel.caretY + "px";
      tooltipEl.style.font = bodyFont.string;
      tooltipEl.style.padding = tooltipModel.padding + "px " + tooltipModel.padding + "px";
      tooltipEl.style.pointerEvents = "none";
    },
    renderChart() {
      let theChart = this;
      const CountryNameData = theChart.cdata;

      if (!CountryNameData) {
        return;
      }

      fetch("https://unpkg.com/world-atlas/countries-50m.json")
        .then((r) => r.json())
        .then((data) => {
          const originalData = data;
          const countries = ChartGeo.topojson.feature(data, data.objects.countries).features;
          var ctx = this.$el.getContext("2d");
          let formatted = [];
          let dataFormatted = [];
          let simpleformatcol = [];

          countries.forEach(function (item) {
            item.properties.value = 0;
            data = {};
            data.feature = item;
            data.value = 0;
            simpleformat = [];

            latlong = item.geometry.coordinates[0][0][0];

            alllatlong = item.geometry.coordinates[0][0];

            if (!CountryNameData) {
              return;
            }

            if (CountryNameData[item.properties.name]) {
              if (alllatlong.length == 2) {
                simpleformat.latitude = latlong[1];
                simpleformat.longitude = latlong[0];
              } else {
                everyLat = 0;
                everyLong = 0;

                alllatlong.forEach(function (latobj) {
                  everyLat += latobj[1];
                  everyLong += latobj[0];
                });

                averageLat = everyLat / alllatlong.length;
                averageLong = everyLong / alllatlong.length;

                simpleformat.latitude = averageLat;
                simpleformat.longitude = averageLong;
              }

              thevalue = CountryNameData[item.properties.name];
              item.properties.value = parseInt(CountryNameData[item.properties.name]);

              data.value = parseInt(thevalue);

              simpleformat.value = 0;
              simpleformat.name = item.properties.name;
              simpleformat.description = item.properties.name;
              simpleformat.value = parseInt(thevalue);
            }

            if (item.properties.name == "United States of America") {
              if (CountryNameData["United States"]) {
                simpleformat.latitude = "41.500000";
                simpleformat.longitude = "-100.000000";

                thevalue = CountryNameData["United States"];
                item.properties.value = parseInt(CountryNameData["United States"]);

                data.value = parseInt(thevalue);
                simpleformat.value = 0;
                simpleformat.name = item.properties.name;
                simpleformat.description = item.properties.name;
                simpleformat.value = parseInt(thevalue);
              }
            }

            if (item.properties.name != "Antarctica") {
              formatted.push(item);
              dataFormatted.push(data);

              if (simpleformat === undefined || simpleformat.length == 00) {
                simpleformatcol.push(simpleformat);
              }
            }
          });

          const bubblechart = new Chart(ctx, {
            type: "bubbleMap",
            data: {
              labels: formatted.map((d) => d.properties.name),
              datasets: [
                {
                  label: theChart.translations.visits,
                  outline: formatted,
                  showOutline: true,
                  backgroundColor: "rgba(247, 127, 212, 0.3)",
                  outlineBackgroundColor: "rgba(12, 92, 239, 0.2)",
                  outlineBorderColor: "rgba(0,0,0,0)",
                  outlineBorderWidth: 2,
                  borderColor: "rgb(247, 127, 212)",
                  data: simpleformatcol,
                },
              ],
            },
            options: {
              borderWidth: 2,
              plugins: {
                legend: {
                  display: false,
                },
                datalabels: {
                  align: "top",
                  formatter: (v) => {
                    return v.description;
                  },
                },
                tooltip: {
                  enabled: false,

                  external: function (context) {
                    theChart.getTooltip(context);
                  },
                },
              },
              scales: {
                xy: {
                  projection: "mercator",
                  backgroundColor: "rgb(222,0,0)",
                },
                r: {
                  size: [1, 20],
                },
              },
            },
          });

          return;
          const chart = new Chart(ctx, {
            type: "bubbleMap",
            data: {
              labels: formatted.map((d) => d.properties.name),
              datasets: [
                {
                  label: theChart.translations.visits,
                  data: dataFormatted,
                },
              ],
            },
            options: {
              borderWidth: 1.5,
              //borderColor: "#333",
              //borderRadius: 50,
              //showOutline: false,
              //showGraticule: false,
              //interpolate: (v) => (v < 0.5 ? "green" : "red"),
              plugins: {
                legend: {
                  display: false,
                },
                scale: {
                  //display: false,
                },
                tooltip: {
                  enabled: false,

                  external: function (context) {
                    theChart.getTooltip(context);
                  },
                },
              },
              scales: {
                xy: {
                  projection: "equalEarth",
                  //projectionScale: 1.2,
                  //projectionOffset: [0, 0],
                  //projection: "equirectangular",
                },
                color: {
                  //quantize: 6,
                  //display: false,
                  interpolate: (v) => {
                    if (v === 0) return "rgba(12, 92, 239, 0.1)";
                    if (v >= 0.1 && v < 0.2) return "rgba(12, 92, 239, 0.4)";
                    if (v >= 0.2 && v < 0.4) return "rgba(12, 92, 239, 0.6)";
                    if (v >= 0.4 && v < 0.6) return "rgba(12, 92, 239, 0.8";
                    if (v >= 0.6 && v < 0.8) return "rgba(12, 92, 239, 0.9)";
                    if (v >= 0.8) return "rgba(12, 92, 239, 1)";
                  },
                  legend: {
                    display: false,
                    position: "bottom-right",
                    align: "bottom",
                  },
                },
              },
            },
          });
        });

      ////
    },
  },
  template: '<canvas  height="200" :dat-sd="dates.startDate" :dat-sed="dates.endDate"></canvas>',
});

uipressOverviewApp.component("draggable", vuedraggable);
//uipressOverviewApp.component("uip-chart", vue3chart3);

//import { Chart, registerables } from "chart.js";

//let Vue3ChartJs = import("../chartjs/vue3-chartjs.es.js");

//uipressOverviewApp.component("vue3-chart-js", Vue3ChartJs);

var fnWithForeach = async (modules) => {
  return await modules.forEach(async (amodule, index) => {
    let theModule = await import(amodule.componentPath);
    let activated = await uipressOverviewApp.component(theModule.moduleName(), theModule.moduleData());
    if (index == modules.length - 1) {
      uipressOverviewApp.mount("#overview-app");
    }
  });
  return;
};

async function buildAPP() {
  let result = await fnWithForeach(uipressOverviewModules);
}

buildAPP();
