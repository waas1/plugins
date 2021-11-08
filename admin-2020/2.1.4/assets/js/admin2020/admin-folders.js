jQuery(document).on("dragstart", ".attachments-browser .attachments .attachment", function (ev) {
  jQuery(".uploader-window").css("visibility", "hidden");

  allIDS = [];
  if (jQuery(".attachments-browser .attachments .attachment[aria-checked='true']").length > 0) {
    jQuery(".attachments-browser .attachments .attachment[aria-checked='true']").each(function (index) {
      tempid = jQuery(this).attr("data-id");
      allIDS.push(tempid);
    });

    ev.originalEvent.dataTransfer.setData("itemID", JSON.stringify(allIDS));
  } else {
    theid = jQuery(ev.currentTarget).attr("data-id");
    ev.originalEvent.dataTransfer.setData("itemID", JSON.stringify([theid]));
  }

  thefiles = "1 file";

  ev.originalEvent.dataTransfer.dropEffect = "move";
  ev.originalEvent.dataTransfer.effectAllowed = "move";
  ev.originalEvent.dataTransfer.setData("type", "content");
  jQuery("#a2020-folder-template").show();

  ///SET DRAG HANDLE

  var elem = document.createElement("div");
  elem.id = "a2020Contentdrag";
  elem.innerHTML = thefiles;
  elem.style.position = "absolute";
  elem.style.top = "-1000px";
  document.body.appendChild(elem);
  ev.originalEvent.dataTransfer.setDragImage(elem, 0, 0);
});

const a2020folders = {
  data() {
    return {
      loading: true,
      folders: {
        allFolders: [],
        openFolders: [],
        activeFolder: [],
        activeFolderObj: [],
        newFolder: {
          name: "",
          color: "#0c5cef",
          parent: "",
        },
        editFolder: {
          name: "",
          color: "",
          id: "",
        },
      },
    };
  },
  computed: {
    queryContent() {
      this.getFolders();
    },
    queryTheFolders() {
      this.queryContent;
      return this.folders.allFolders;
    },
  },
  mounted: function () {},
  watch: {},
  methods: {
    //////TITLE: Adds a batch rename option/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////

    //////TITLE: GETS ALL FOLDERS/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    getFolders() {
      self = this;
      this.loading = true;

      jQuery.ajax({
        url: admin2020_folder_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_get_folders_legacy",
          security: admin2020_folder_ajax.security,
        },
        success: function (response) {
          data = JSON.parse(response);
          self.loading = false;

          if (data) {
            self.folders.allFolders = data;
            return self.folders.allFolders;
          }
        },
      });
    },
    //////TITLE: CREATES NEW FOLDER/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    createFolder() {
      self = this;
      self.loading = true;
      self.folders.newFolder.parent = self.folders.activeFolder[0];
      folders = self.folders.newFolder;

      jQuery.ajax({
        url: admin2020_folder_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_create_folder_legacy",
          security: admin2020_folder_ajax.security,
          folders: folders,
        },
        success: function (response) {
          self.loading = false;
          data = JSON.parse(response);

          if (data.error) {
            ///SOMETHING WENT WRONFG
            UIkit.notification(data.error, { pos: "bottom-left", status: "danger" });
            return;
          }

          if (data) {
            UIkit.notification(data.message, { pos: "bottom-left", status: "success" });
            self.folders.newFolder.name = "";
            self.folders.newFolder.parent = "";
            return self.getFolders();
          }
        },
      });
    },

    confirmDeleteFolder() {
      self = this;
      UIkit.modal.confirm("Are you sure you want to delete this folder?").then(
        function () {
          self.deleteFolder();
        },
        function () {
          ///DON'T DELETE
        }
      );
    },
    viewAllMediaWithoutFolder() {
      this.folders.activeFolder = ["uncat"];
      wp.media.frame.content.get().collection.props.set({ folder_id: "uncat" });
    },

    viewAllMedia() {
      this.folders.activeFolder = [];
      wp.media.frame.content.get().collection.props.set({ folder_id: "" });
    },
    //////TITLE: EDITS FOLDER/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    editFolder(editFolder) {
      self = this;

      self.folders.editFolder.name = editFolder.title;
      self.folders.editFolder.color = editFolder.color;
      self.folders.editFolder.id = editFolder.id;
      jQuery("#updatefolderpanel").show();
    },

    updateFolder() {
      self = this;
      thefolder = self.folders.editFolder;

      jQuery.ajax({
        url: admin2020_folder_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_update_folder_legacy",
          security: admin2020_folder_ajax.security,
          thefolder: thefolder,
        },
        success: function (response) {
          self.loading = false;
          data = JSON.parse(response);
          if (data.error) {
            ///SOMETHING WENT WRONG
            UIkit.notification(data.error, { pos: "bottom-left", status: "danger" });
            return;
          }

          if (data) {
            UIkit.notification(data.message, { pos: "bottom-left", status: "success" });
            jQuery("#updatefolderpanel").hide();
            return self.getFolders();
          }
        },
      });
    },
    //////TITLE: DELETES FOLDER/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    deleteFolder() {
      self = this;
      self.loading = true;
      activeFolder = self.folders.activeFolder[0];

      jQuery.ajax({
        url: admin2020_folder_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_delete_folder_legacy",
          security: admin2020_folder_ajax.security,
          activeFolder: activeFolder,
        },
        success: function (response) {
          self.loading = false;
          data = JSON.parse(response);

          if (data.error) {
            ///SOMETHING WENT WRONFG
            UIkit.notification(data.error, { pos: "bottom-left", status: "danger" });
            return;
          }

          if (data) {
            UIkit.notification(data.message, { pos: "bottom-left", status: "success" });
            self.folders.activeFolder = [];
            return self.getFolders();
          }
        },
      });
    },
    //////TITLE: MAKE FOLDER TOP LEVEL/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    dropInTopLevel(evt) {
      var itemID = evt.dataTransfer.getData("itemID");
      var dropItemType = evt.dataTransfer.getData("type");
      if (dropItemType == "folder") {
        this.moveFolder(itemID, "toplevel");
      }
      if (dropItemType == "content") {
        this.moveContentToFolder(JSON.parse(itemID), "toplevel");
      }
      jQuery("#a2020-folder-template").hide();
    },

    moveContentToFolder(contentID, destinationId) {
      self = this;

      jQuery.ajax({
        url: admin2020_folder_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_move_content_to_folder_legacy",
          security: admin2020_folder_ajax.security,
          contentID: contentID,
          destinationId: destinationId,
        },
        success: function (response) {
          data = JSON.parse(response);

          if (data.error) {
            ///SOMETHING WENT WRONG
            UIkit.notification(data.error, { pos: "bottom-left", status: "danger" });
          } else {
            ///FOLDER MOVED
            UIkit.notification(data.message, { pos: "bottom-left", status: "succes" });
            return self.getFolders();
          }
        },
      });
    },
    //////TITLE: MOVES FOLDER/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    moveFolder(folderiD, destinationId) {
      self = this;

      jQuery.ajax({
        url: admin2020_folder_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_move_folder_legacy",
          security: admin2020_folder_ajax.security,
          folderiD: folderiD,
          destinationId: destinationId,
        },
        success: function (response) {
          data = JSON.parse(response);

          if (data.error) {
            ///SOMETHING WENT WRONG
            UIkit.notification(data.error, { pos: "bottom-left", status: "danger" });
          } else {
            ///FOLDER MOVED
            UIkit.notification(data.message, { pos: "bottom-left", status: "success" });
            return self.getFolders();
          }
        },
      });
    },
    ////DRAG & DROP//////
    //////TITLE: SETS DATA FOR ITEM DRAG/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    startContentDrag(evt, item) {
      allSelected = this.contentTable.selected;

      if (allSelected.length > 0) {
        evt.dataTransfer.setData("itemID", JSON.stringify(allSelected));
        thefiles = allSelected.length + " files";
      } else {
        evt.dataTransfer.setData("itemID", JSON.stringify([item.id]));
        thefiles = "1 file";
      }

      evt.dataTransfer.dropEffect = "move";
      evt.dataTransfer.effectAllowed = "move";
      evt.dataTransfer.setData("type", "content");
      jQuery("#a2020-folder-template").show();

      ///SET DRAG HANDLE

      var elem = document.createElement("div");
      elem.id = "a2020Contentdrag";
      elem.innerHTML = thefiles;
      elem.style.position = "absolute";
      elem.style.top = "-1000px";
      document.body.appendChild(elem);
      evt.dataTransfer.setDragImage(elem, 0, 0);
    },

    endContentDrag(evt, item) {
      jQuery("#a2020-folder-template").hide();
    },
    ////CHECK IF SOMETHING IS IN A ARRAY
    isIn(option, options) {
      return options.includes(option);
    },
    //////TITLE: REMOVES ITEM FROM ARRAY/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    removeFromList(option, options) {
      const index = options.indexOf(option);
      if (index > -1) {
        options = options.splice(index, 1);
      }
    },
  },
};

///BUILD VUE APP
const a2020foldersapp = a2020Vue.createApp(a2020folders);

a2020foldersapp.component("folder-template", {
  props: {
    folder: Object,
    open: Array,
    current: Array,
  },
  data: function () {
    return {
      currentFolderObject: [],
      dragCounter: 0,
      currentFrame: "",
    };
  },
  methods: {
    isIn(folderId) {
      return this.open.includes(folderId);
    },
    isActive(folderId) {
      return this.current.includes(folderId);
    },
    folderToggle(folderId) {
      const index = this.open.indexOf(folderId);
      if (index > -1) {
        options = this.open.splice(index, 1);
      } else {
        this.open.push(folderId);
      }
    },
    makeActive(thefolder, currentFolder) {
      currentFolder[0] = thefolder.id;
      self = this;
      if (wp.media.frames.browse) {
        wp.media.frames.browse.content.get().collection.props.set({ folder_id: thefolder.id });
      } else {
        wp.media.frame.content.get().collection.props.set({ folder_id: thefolder.id });
      }
    },
    editFolder(theFolder) {
      this.$parent.editFolder(theFolder);
    },
    moveFolder(folderiD, destinationId) {
      self = this;
      this.dragCounter = 0;

      jQuery.ajax({
        url: admin2020_folder_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_move_folder_legacy",
          security: admin2020_folder_ajax.security,
          folderiD: folderiD,
          destinationId: destinationId,
        },
        success: function (response) {
          data = JSON.parse(response);

          if (data.error) {
            ///SOMETHING WENT WRONG
            UIkit.notification(data.error, { pos: "bottom-left", status: "danger" });
          } else {
            ///FOLDER MOVED
            UIkit.notification(data.message, { pos: "bottom-left", status: "succes" });
            return self.refreshFolders();
          }
        },
      });
    },
    moveContentToFolder(contentID, destinationId) {
      self = this;
      this.dragCounter = 0;

      theids = JSON.parse(contentID);

      jQuery.ajax({
        url: admin2020_folder_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_move_content_to_folder_legacy",
          security: admin2020_folder_ajax.security,
          contentID: theids,
          destinationId: destinationId,
        },
        success: function (response) {
          data = JSON.parse(response);

          if (data.error) {
            ///SOMETHING WENT WRONG
            UIkit.notification(data.error, { pos: "bottom-left", status: "danger" });
          } else {
            ///FOLDER MOVED
            UIkit.notification(data.message, { pos: "bottom-left", status: "succes" });
            self.$root.getFolders();
          }
        },
      });
    },
    refreshFolders() {
      this.$root.getFolders();
    },
    //////DRAG AND DROP
    startFolderDrag(evt, item) {
      evt.dataTransfer.dropEffect = "move";
      evt.dataTransfer.effectAllowed = "move";
      evt.dataTransfer.setData("itemID", item.id);
      evt.dataTransfer.setData("type", "folder");
      jQuery("#a2020-folder-template").show();
      jQuery(".uploader-window").remove();
    },

    dropInfolder(evt, folder) {
      this.dragCounter = 0;
      var itemID = evt.dataTransfer.getData("itemID");
      var dropItemType = evt.dataTransfer.getData("type");

      if (dropItemType == "folder") {
        this.moveFolder(itemID, folder.id);
      }
      if (dropItemType == "content") {
        this.moveContentToFolder(itemID, JSON.parse(folder.id));
      }
      jQuery(".valid_drop").removeClass("valid_drop");
      jQuery("#a2020-folder-template").hide();
    },
    addDropClass(evt, folder) {
      evt.preventDefault();
      target = evt.target;
      this.dragCounter++;
      if (jQuery(target).hasClass("folder_block")) {
        jQuery(target).addClass("valid_drop");
      } else {
        jQuery(target).closest(".folder_block").addClass("valid_drop");
      }
    },
    removeDropClass(evt, folder) {
      evt.preventDefault();
      target = evt.target;
      this.dragCounter--;

      if (this.dragCounter != 0) {
        return;
      }
      if (jQuery(target).hasClass("folder_block")) {
        jQuery(target).removeClass("valid_drop");
      } else {
        jQuery(target).closest(".folder_block").removeClass("valid_drop");
      }
    },
  },
  template:
    '<div class="a2020-folder-component "  :data-folder-id="folder.id">\
      <div class="uk-flex uk-flex-middle folder_block" draggable="true"\
      @dragstart="startFolderDrag($event,folder)"\
      @drop="dropInfolder($event, folder)" \
      @dragenter="addDropClass($event, folder)"\
      @dragleave="removeDropClass($event, folder)"\
      @dragover.prevent\
      @dragenter.prevent\
       :class="[{\'folder_open\' : isIn(folder.id)}, {\'active-folder\' : isActive(folder.id)} ]">\
        <span class="material-icons-outlined uk-text-muted folderChevron" :class="{\'nosub\' : !folder.hasOwnProperty(\'subs\')}" @click="folderToggle(folder.id)">\
          chevron_right\
        </span>\
        <span class="uk-flex uk-flex-middle folder-click" @click="makeActive(folder,current)">\
          <span class="material-icons  uk-margin-small-right " :style="{\'color\': folder.color}">folder</span>\
          <span class=" uk-text-bold">{{folder.title}}</span>\
        </span>\
        <span class="material-icons  uk-margin-small-left a2020-folder-edit"  @click="editFolder(folder)">edit</span>\
        <span class="uk-text-muted uk-text-bold a2020-folder-count">{{folder.count}}</span>\
      </div>\
      <template v-if="folder.subs" >\
        <div class="a2020-folder-sub" v-if="isIn(folder.id)">\
          <template v-for="subfolder in folder.subs">\
            <folder-template :folder="subfolder" :open="open" :current="current" ></folder-template>\
          </template>\
        </div>\
      </template>\
    </div>\
    ',
});
