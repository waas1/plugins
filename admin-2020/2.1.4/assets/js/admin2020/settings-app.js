//import { CodeJar } from "https://medv.io/codejar/codejar.js";

//const a2020adminBarPreferences = JSON.parse(admin2020_admin_bar_ajax.preferences);
const a2020optionsObject = JSON.parse(a2020_settings_app_ajax.optionsObject);
const a2020postTypes = JSON.parse(a2020_settings_app_ajax.postTypes);
const a2020network = a2020_settings_app_ajax.network == true;
const a2020activated = a2020_settings_app_ajax.activated == "true";
const a2020premium = a2020_settings_app_ajax.premium == true;
var mediaUploader;

console.log(a2020premium);

const a2020settings = {
  data() {
    return {
      loading: true,
      screenWidth: window.innerWidth,
      settings: a2020optionsObject,
      activeTab: "admin2020_general",
      activated: a2020activated,
      premium: a2020premium,
      search: {
        string: "",
      },
    };
  },
  created: function () {
    window.addEventListener("resize", this.getScreenWidth);
  },
  computed: {
    filteredSearch() {
      return this.search.results;
    },
  },
  mounted: function () {
    //console.log(this.settings);
  },

  methods: {
    getDataFromComp(originalcode, editedcode) {
      return editedcode;
    },
    export_settings() {
      self = this;
      ALLoptions = JSON.stringify(self.settings);

      var today = new Date();
      var dd = String(today.getDate()).padStart(2, "0");
      var mm = String(today.getMonth() + 1).padStart(2, "0"); //January is 0!
      var yyyy = today.getFullYear();

      date_today = mm + "_" + dd + "_" + yyyy;
      filename = "admin2020_settings_" + date_today + ".json";

      var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(ALLoptions);
      var dlAnchorElem = document.getElementById("admin2020_download_settings");
      dlAnchorElem.setAttribute("href", dataStr);
      dlAnchorElem.setAttribute("download", filename);
      dlAnchorElem.click();
    },
    import_settings() {
      self = this;

      var thefile = jQuery("#admin2020_export_settings")[0].files[0];

      if (thefile.type != "application/json") {
        window.alert("Please select a valid JSON file.");
        return;
      }

      if (thefile.size > 100000) {
        window.alert("File is to big.");
        return;
      }

      var file = document.getElementById("admin2020_export_settings").files[0];
      var reader = new FileReader();
      reader.readAsText(file, "UTF-8");

      reader.onload = function (evt) {
        json_settings = evt.target.result;
        parsed = JSON.parse(json_settings);

        if (parsed != null) {
          ///GOOD TO GO;
          self.settings = parsed;
          UIkit.notification("Settings imported", { pos: "bottom-left", status: "success" });
          self.saveSettings();
        } else {
          UIkit.notification("something wrong", { pos: "bottom-left", status: "danger" });
        }
      };
    },
    saveSettings() {
      self = this;
      allsettings = self.settings;

      jQuery.ajax({
        url: a2020_settings_app_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_save_settings_from_app",
          security: a2020_settings_app_ajax.security,
          allsettings: allsettings,
          a2020network: a2020network,
        },
        success: function (response) {
          data = JSON.parse(response);

          if (data.error) {
            ///SOMETHING WENT WRONG
            UIkit.notification(data.error, { pos: "bottom-left", status: "danger" });
            return;
          }
          UIkit.notification(data.message, { pos: "bottom-left", status: "success" });
        },
      });
    },

    reset_settings() {
      self = this;

      jQuery.ajax({
        url: a2020_settings_app_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_reset_settings",
          security: a2020_settings_app_ajax.security,
          a2020network: a2020network,
        },
        success: function (response) {
          data = JSON.parse(response);

          if (data.error) {
            ///SOMETHING WENT WRONG
            UIkit.notification(data.error, { pos: "bottom-left", status: "danger" });
            return;
          }
          UIkit.notification(data.message, { pos: "bottom-left", status: "success" });
          location.reload();
        },
      });
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
    highlighter(code) {
      // js highlight example
      return Prism.highlight(code, Prism.languages.js, "js");
    },
    removeLicence() {
      self = this;

      jQuery.ajax({
        url: a2020_settings_app_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_remove_licence",
          security: a2020_settings_app_ajax.security,
          network: a2020network,
        },
        success: function (response) {
          data = JSON.parse(response);

          if (data.error) {
            ///SOMETHING WENT WRONG
            UIkit.notification(data.error, { pos: "bottom-left", status: "danger" });
            return;
          }

          UIkit.notification(data.message, { pos: "bottom-left", status: "success" });

          window.setInterval(() => {
            location.reload();
          }, 500);
        },
      });
    },
    chooseImage(theOption) {
      mediaUploader = wp.media.frames.file_frame = wp.media({
        title: "Choose Image",
        button: {
          text: "Choose Image",
        },
        multiple: false,
      });
      mediaUploader.on("select", function () {
        var attachment = mediaUploader.state().get("selection").first().toJSON();
        theOption.value = attachment.url;
      });
      mediaUploader.open();
    },
    typeArray(thearray) {
      if (!Array.isArray(thearray)) {
        thearray = [];
      }
    },
  },
};

const a2020adminSettings = a2020Vue.createApp(a2020settings);

a2020adminSettings.component("multi-select", {
  data: function () {
    return {
      thisSearchInput: "",
      options: [],
    };
  },
  props: {
    selected: Array,
    name: String,
    placeholder: String,
    single: Boolean,
  },
  watch: {
    thisSearchInput: function (newValue, oldValue) {
      self = this;

      if (newValue.length > 0) {
        jQuery.ajax({
          url: a2020_settings_app_ajax.ajax_url,
          type: "post",
          data: {
            action: "a2020_get_users_and_roles",
            security: a2020_settings_app_ajax.security,
            searchString: newValue,
          },
          success: function (response) {
            data = JSON.parse(response);

            if (data.error) {
              ///SOMETHING WENT WRONG
              UIkit.notification(data.error, { pos: "bottom-left", status: "danger" });
              return;
            }

            self.options = data.roles;
          },
        });
      }
    },
  },
  methods: {
    //////TITLE: ADDS A SLECTED OPTION//////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////DESCRIPTION: ADDS A SELECTED OPTION FROM OPTIONS
    addSelected(selectedoption, options) {
      if (this.single == true) {
        options[0] = selectedoption;
      } else {
        options.push(selectedoption);
      }
    },
    //////TITLE: REMOVES A SLECTED OPTION//////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////DESCRIPTION: ADDS A SELECTED OPTION FROM OPTIONS
    removeSelected(option, options) {
      const index = options.indexOf(option);
      if (index > -1) {
        options = options.splice(index, 1);
      }
    },

    //////TITLE:  CHECKS IF SELECTED OR NOT//////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////DESCRIPTION: ADDS A SELECTED OPTION FROM OPTIONS
    ifSelected(option, options) {
      const index = options.indexOf(option);
      if (index > -1) {
        return false;
      } else {
        return true;
      }
    },
    //////TITLE:  CHECKS IF IN SEARCH//////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////DESCRIPTION: CHECKS IF ITEM CONTAINS STRING
    ifInSearch(option, searchString) {
      item = option.toLowerCase();
      string = searchString.toLowerCase();

      if (item.includes(string)) {
        return true;
      } else {
        return false;
      }
    },
  },
  template:
    '<div class="a2020-select-container"> \
    <div class=" uk-flex uk-flex-wrap">\
    <span v-if="selected.length < 1" class="selected-item" style="background: none;">\
      <span class="uk-text-meta">{{name}}...</span>\
    </span>\
    <span v-if="selected.length > 0" v-for="select in selected" class="selected-item">\
       <div>\
         {{select}}\
      <a class="uk-margin-small-left" href="#" @click="removeSelected(select,selected)">x</a>\
       </div>\
    </span>\
    </div>\
  </div>\
  <div class="uk-dropdown wpteams-no-after a2020-available-container " uk-dropdown="pos:bottom-justify;mode:click;offset:10;">\
    <div class="uk-inline uk-width-1-1 wpteams-border uk-margin-small-bottom">\
    <span class="uk-form-icon" uk-icon="icon: search" style="	left: -8px;"></span>\
    <input class="uk-input uk-search-input " type="text" style="background: none;border:none;" \
    :placeholder="placeholder" v-model="thisSearchInput">\
    </div>\
    <div class="">\
    <template v-for="option in options">\
      <span  class="available-item" \
      @click="addSelected(option.name, selected)" \
      v-if="ifSelected(option.name, selected) && ifInSearch(option.name, thisSearchInput)" \
      style="cursor: pointer">\
      {{option.label}}\
      </span>\
    </template>\
    </div>\
  </div>\
    ',
});

a2020adminSettings.component("multi-select-posts", {
  data: function () {
    return {
      thisSearchInput: "",
      options: a2020postTypes,
    };
  },
  props: {
    selected: Array,
    name: String,
    placeholder: String,
    single: Boolean,
  },
  methods: {
    //////TITLE: ADDS A SLECTED OPTION//////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////DESCRIPTION: ADDS A SELECTED OPTION FROM OPTIONS
    addSelected(selectedoption, options) {
      if (this.single == true) {
        options[0] = selectedoption;
      } else {
        options.push(selectedoption);
      }
    },
    //////TITLE: REMOVES A SLECTED OPTION//////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////DESCRIPTION: ADDS A SELECTED OPTION FROM OPTIONS
    removeSelected(option, options) {
      const index = options.indexOf(option);
      if (index > -1) {
        options = options.splice(index, 1);
      }
    },

    //////TITLE:  CHECKS IF SELECTED OR NOT//////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////DESCRIPTION: ADDS A SELECTED OPTION FROM OPTIONS
    ifSelected(option, options) {
      const index = options.indexOf(option);
      if (index > -1) {
        return false;
      } else {
        return true;
      }
    },
    //////TITLE:  CHECKS IF IN SEARCH//////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////DESCRIPTION: CHECKS IF ITEM CONTAINS STRING
    ifInSearch(option, searchString) {
      item = option.toLowerCase();
      string = searchString.toLowerCase();

      if (item.includes(string)) {
        return true;
      } else {
        return false;
      }
    },
  },
  template:
    '<div class="a2020-select-container"> \
    <div class=" uk-flex uk-flex-wrap">\
    <span v-if="selected.length < 1" class="selected-item" style="background: none;">\
      <span class="uk-text-meta"> {{name}}...</span>\
    </span>\
    <span v-if="selected.length > 0" v-for="select in selected" class="selected-item">\
       <div>\
         {{select}}\
      <a class="uk-margin-small-left" href="#" @click="removeSelected(select,selected)">x</a>\
       </div>\
    </span>\
    </div>\
  </div>\
  <div class="uk-dropdown wpteams-no-after a2020-available-container " uk-dropdown="pos:bottom-justify;mode:click;offset:10;">\
    <div class="uk-inline uk-width-1-1 wpteams-border uk-margin-small-bottom">\
    <span class="uk-form-icon" uk-icon="icon: search" style="	left: -8px;"></span>\
    <input class="uk-input uk-search-input " type="text" style="background: none;border:none;" \
    :placeholder="placeholder" v-model="thisSearchInput">\
    </div>\
    <div class="">\
    <template v-for="option in options">\
      <span  class="available-item" \
      @click="addSelected(option.name, selected)" \
      v-if="ifSelected(option.name, selected) && ifInSearch(option.name, thisSearchInput)" \
      style="cursor: pointer">\
      {{option.label}}\
      </span>\
    </template>\
    </div>\
  </div>\
    ',
});

a2020adminSettings.component("font-select", {
  data: function () {
    return {
      fontSearch: "",
      options: [],
      allFontsData: [],
    };
  },
  props: {
    selected: Array,
    name: String,
    placeholder: String,
    single: Boolean,
  },
  watch: {
    fontSearch: function (newValue, oldValue) {
      this.options = this.filterIt(this.allFontsData, this.fontSearch);
    },
    options: function (newValue, oldValue) {
      currentOptions = this.options.slice(0, 20);

      for (let index = 0; index < currentOptions.length; ++index) {
        currentFont = currentOptions[index];
        var css = "@import url('https://fonts.googleapis.com/css2?family=" + currentFont.fontName + "&display=swap');";
        jQuery("<style/>").append(css).appendTo(document.head);
      }
    },
  },
  mounted: function () {
    //console.log(this.selected);
  },
  computed: {
    runitonce() {
      this.queryFonts();
    },
    allFonts() {
      this.runitonce;
      return this.options.slice(0, 30);
    },
  },
  methods: {
    queryFonts() {
      var self = this;

      jQuery.getJSON("https://www.googleapis.com/webfonts/v1/webfonts?sort=popularity&key=AIzaSyCsOWMT4eyd1vd4yN0-h7jZnXSCf2qDmio", function (fonts) {
        var filteredFonts = [];
        allfonts = fonts.items;
        formattedFonts = [];

        jQuery.each(allfonts, function (k, v) {
          temp = [];
          temp.fontName = v.family;
          temp.category = v.category;

          str = "";
          font = str.concat("'", temp.fontName, "', ", temp.category);

          temp.fontFamily = font;
          formattedFonts.push(temp);
        });

        listfonts = formattedFonts;
        self.allFontsData = listfonts;
        self.options = listfonts;
      });
    },
    //////TITLE: ADDS A SLECTED OPTION//////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////DESCRIPTION: ADDS A SELECTED OPTION FROM OPTIONS
    addSelected(selectedoption, options) {
      if (this.single == true) {
        options[0] = selectedoption;
      } else {
        options.push(selectedoption);
      }
    },
    filterIt(arr, searchKey) {
      return arr.filter(function (obj) {
        return Object.keys(obj).some(function (key) {
          return obj[key].toLowerCase().includes(searchKey.toLowerCase());
        });
      });
    },
    //////TITLE: REMOVES A SLECTED OPTION//////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////DESCRIPTION: ADDS A SELECTED OPTION FROM OPTIONS
    removeSelected(option, options) {
      this.selected[0] = "";
      this.selected[1] = "";
    },

    //////TITLE:  CHECKS IF SELECTED OR NOT//////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////DESCRIPTION: ADDS A SELECTED OPTION FROM OPTIONS
    ifSelected(option, options) {
      const index = options.indexOf(option);
      if (index > -1) {
        return false;
      } else {
        return true;
      }
    },
    //////TITLE:  CHECKS IF IN SEARCH//////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////DESCRIPTION: CHECKS IF ITEM CONTAINS STRING
    ifInSearch(option, searchString) {
      item = option.toLowerCase();
      string = searchString.toLowerCase();

      if (item.includes(string)) {
        return true;
      } else {
        return false;
      }
    },
    saveFont(font, chosen) {
      this.selected[0] = font.fontName;
      this.selected[1] = font.category;
    },
  },
  template:
    '<div class="a2020-select-container"> \
    <div class=" uk-flex uk-flex-wrap">\
      <span v-if="!selected[0]" class="selected-item" style="background: none;">\
        <span class="uk-text-meta">Select {{name}}...</span>\
      </span>\
      <span v-if="selected[0]"  class="selected-item">\
        <div>\
          {{selected[0]}}\
          <a class="uk-margin-small-left" href="#" @click="removeSelected(select,selected)">x</a>\
        </div>\
      </span>\
    </div>\
  </div>\
  <div class="uk-dropdown wpteams-no-after a2020-available-container " uk-dropdown="pos:bottom-justify;mode:click;offset:10;">\
    <div class="uk-inline uk-width-1-1 wpteams-border uk-margin-small-bottom">\
      <span class="uk-form-icon" uk-icon="icon: search" style="	left: -8px;"></span>\
      <input class="uk-input uk-search-input " type="text" style="background: none;border:none;" \
      :placeholder="placeholder" v-model="fontSearch">\
    </div>\
    <div class="uk-height-medium uk-overflow-auto">\
      <ul v-for="option in allFonts">\
        <li @click="saveFont(option, selected)" class="a2020-font-medium" v-bind:style="{ \'font-family\': option.fontFamily}"><a href="#" class="uk-link-muted">{{option.fontName}}</a></li>\
      </ul>\
    </div>\
  </div>\
    ',
});

const highlight = (editor) => {
  editor.textContent = editor.textContent;
  hljs.highlightBlock(editor);
};

let editorOptions = {
  tab: " ".repeat(2), // default is \t
};

a2020adminSettings.component("code-flask", {
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

a2020adminSettings.mount("#a2020-settings-app");
