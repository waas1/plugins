const a2020prefs = JSON.parse(a2020_content_ajax.a2020_content_prefs);

const wpteams = {
  data() {
    return {
      loading: true,
      upload: false,
      masterLoader: false,
      contentTable: {
        content: [],
        total: 0,
        test: true,
        currentPage: 1,
        totalPages: 1,
        selected: [],
        selectAll: false,
        postTypes: [],
        postStatuses: [],
        fileTypes: [],
        categories: [],
        tags: [],
        mode: a2020prefs.viewMode,
        gridSize: a2020prefs.gridSize,
        folderPanel: a2020prefs.folderView == "true",
        views: {
          allViews: [],
          currentView: [],
        },
        filters: {
          search: "",
          selectedPostTypes: [],
          selectedPostStatuses: [],
          selectedFileTypes: [],
          selectedCategories: [],
          selectedTags: [],
          date: "",
          dateComparison: "on",
          perPage: a2020prefs.perPage,
          activeFolder: 0,
        },
      },
      newView: {
        name: "",
      },
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
      quickEdit: {
        id: "",
        title: "",
        status: "",
        author: "",
        created: "",
        modified: "",
        postType: "",
        url: "",
        selectedCategories: [],
        selectedStatus: [],
        selectedTags: [],
      },
      batchUpdate: {
        tags: [],
        categories: [],
        replaceTags: false,
        replaceCats: false,
      },
      batchRename: {
        renameTypes: a2020prefs.renameOptions,
        selectedAttribute: "name",
        metaKey: "",
        selectedTypes: [],
        selectedOption: 0,
        preview: [],
      },
    };
  },
  computed: {
    queryContent() {
      this.getFiles();
      this.getFolders();
    },
    fileList() {
      this.queryContent;
      return this.contentTable.content;
    },
  },
  mounted: function () {
    window.setInterval(() => {
      ///TIMED FUNCTIONS
    }, 15000);
    self = this;
    this.masterLoader = true;
    ////SETUP FILE POND
    jQuery.fn.filepond.registerPlugin(FilePondPluginFileEncode);
    jQuery.fn.filepond.registerPlugin(FilePondPluginFileValidateSize);
    jQuery.fn.filepond.registerPlugin(FilePondPluginImageExifOrientation);
    jQuery.fn.filepond.registerPlugin(FilePondPluginFileValidateType);

    jQuery.fn.filepond.setDefaults({
      acceptedFileTypes: JSON.parse(a2020_content_ajax.a2020_allowed_types),
      allowRevert: false,
    });

    jQuery("#a2020_file_upload").filepond();

    FilePond.setOptions({
      server: {
        url: a2020_content_ajax.ajax_url,
        type: "post",
        process: {
          url: "?action=a2020_process_upload&security=" + a2020_content_ajax.security + "&folder=" + self.folders.activeFolder[0],
          method: "POST",
          ondata: (formData) => {
            formData.append("folder", self.folders.activeFolder[0]);
            return formData;
          },
          onload: (res) => {
            // select the right value in the response here and return
            if (res) {
              data = JSON.parse(res);

              if (data.error) {
                return res;
              }

              self.getFiles();
              self.getFolders();
            }
            return res;
          },
        },
      },
    });
  },
  watch: {
    "contentTable.filters.perPage": function (newValue, oldValue) {
      if (newValue != oldValue) {
        a2020_save_user_prefences("content_per_page", newValue, false);
      }
    },
    "contentTable.gridSize": function (newValue, oldValue) {
      if (newValue != oldValue) {
        a2020_save_user_prefences("content_grid_size", newValue, false);
      }
    },
    "contentTable.mode": function (newValue, oldValue) {
      if (newValue != oldValue) {
        a2020_save_user_prefences("content_view_mode", newValue, false);
      }
    },
  },
  methods: {
    //////TITLE: Adds a batch rename option/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    addBatchNameOption() {
      selectedOption = this.batchRename.selectedOption;

      temp = {};
      temp.name = selectedOption;
      temp.primaryValue = null;
      temp.secondaryValue = null;

      this.batchRename.selectedTypes.push(temp);
    },

    removeBatchOption(index) {
      this.batchRename.selectedTypes.splice(index, 1);
    },
    moveBatchOptionUp(currentIndex) {
      this.array_move(this.batchRename.selectedTypes, currentIndex, currentIndex - 1);
    },
    moveBatchOptionDown(currentIndex) {
      this.array_move(this.batchRename.selectedTypes, currentIndex, currentIndex + 1);
    },
    array_move(arr, old_index, new_index) {
      if (new_index >= arr.length) {
        var k = new_index - arr.length + 1;
        while (k--) {
          arr.push(undefined);
        }
      }
      arr.splice(new_index, 0, arr.splice(old_index, 1)[0]);
    },
    batchRenamePreview() {
      self = this;
      selected = this.contentTable.selected;

      options = self.batchRename.selectedTypes;
      fieldToRename = this.batchRename.selectedAttribute;
      metaKey = this.batchRename.metaKey;

      jQuery.ajax({
        url: a2020_content_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_batch_rename_preview",
          security: a2020_content_ajax.security,
          selected: selected,
          batchoptions: options,
          fieldToRename: fieldToRename,
          metaKey: metaKey,
        },
        success: function (response) {
          data = JSON.parse(response);

          if (data.error) {
            ///SOMETHING WENT WRONG
            UIkit.notification(data.error, { pos: "bottom-left", status: "danger" });
            return;
          }

          self.batchRename.preview = data.newnames;
        },
      });
    },

    batchRenameProcess() {
      self = this;
      selected = this.contentTable.selected;

      options = self.batchRename.selectedTypes;
      fieldToRename = this.batchRename.selectedAttribute;
      metaKey = this.batchRename.metaKey;

      jQuery.ajax({
        url: a2020_content_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_process_batch_rename",
          security: a2020_content_ajax.security,
          selected: selected,
          batchoptions: options,
          fieldToRename: fieldToRename,
          metaKey: metaKey,
        },
        success: function (response) {
          data = JSON.parse(response);

          if (data.error) {
            ///SOMETHING WENT WRONG
            UIkit.notification(data.error, { pos: "bottom-left", status: "danger" });
            return;
          }

          UIkit.notification(data.message, { pos: "bottom-left", status: "success" });
          self.getFiles();
          self.batchRename.preview = [];
        },
      });
    },
    //////TITLE: GETS APP CONTENT/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////DESCRIPTION: MAIN QUERY FOR CONTENT
    getFiles() {
      self = this;
      this.loading = true;

      self.contentTable.filters.activeFolder = self.folders.activeFolder[0];

      searchString = self.contentTable.filters.search;
      page = self.contentTable.currentPage;
      types = self.contentTable.filters.selectedPostTypes;
      statuses = self.contentTable.filters.selectedPostStatuses;
      filters = self.contentTable.filters;

      jQuery.ajax({
        url: a2020_content_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_get_content",
          security: a2020_content_ajax.security,
          searchString: searchString,
          page: page,
          types: types,
          statuses: statuses,
          filters: filters,
        },
        success: function (response) {
          data = JSON.parse(response);

          if (data) {
            self.contentTable.content = data.content;
            self.contentTable.total = data.total;
            self.contentTable.totalPages = data.totalPages;
            self.contentTable.postTypes = data.postTypes;
            self.contentTable.postStatuses = data.postStatuses;
            self.contentTable.fileTypes = data.fileTypes;
            self.contentTable.categories = data.categories;
            self.contentTable.tags = data.tags;
            self.contentTable.views.allViews = data.views;

            self.loading = false;

            if (page < 1 || !page || page > self.contentTable.totalPages) {
              page = 1;
              self.contentTable.currentPage = 1;
            }

            return self.contentTable.content;
          }
        },
      });
    },
    nameNewView() {
      UIkit.dropdown(".content-table-filters").hide();
      UIkit.modal(" #new-view-modal").show();
    },

    //////TITLE: GETS ALL FOLDERS/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    getFolders() {
      self = this;
      this.loading = true;

      jQuery.ajax({
        url: a2020_content_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_get_folders",
          security: a2020_content_ajax.security,
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
    openCatsTags() {
      UIkit.modal("#tags-cats-modal").show();
    },
    switchFolderPanel() {
      this.contentTable.folderPanel = !this.contentTable.folderPanel;
      a2020_save_user_prefences("content_folder_view", this.contentTable.folderPanel, false);
    },

    //////TITLE: CREATES NEW FOLDER/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    createFolder() {
      self = this;
      self.loading = true;
      self.folders.newFolder.parent = self.folders.activeFolder[0];
      folders = self.folders.newFolder;

      jQuery.ajax({
        url: a2020_content_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_create_folder",
          security: a2020_content_ajax.security,
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
      UIkit.modal.confirm("Are you sure you want to delete this folder?").then(
        function () {
          self.deleteFolder();
        },
        function () {
          ///DON'T DELETE
        }
      );
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
        url: a2020_content_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_update_folder",
          security: a2020_content_ajax.security,
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
        url: a2020_content_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_delete_folder",
          security: a2020_content_ajax.security,
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
    //////TITLE: OPENS QUICK EDIT/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    openQuickEdit(itemid) {
      self = this;

      jQuery.ajax({
        url: a2020_content_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_open_quick_edit",
          security: a2020_content_ajax.security,
          itemid: itemid,
        },
        success: function (response) {
          data = JSON.parse(response);
          self.quickEdit = data;
          UIkit.modal("#a2020-quick-edit-modal").show();
        },
      });
    },
    openImageEdit() {
      var imgSRC = this.quickEdit.src;
      self = this;

      var {
        createDefaultImageReader,
        createDefaultImageWriter,
        locale_en_gb,
        setPlugins,
        plugin_crop,
        plugin_crop_defaults,
        plugin_crop_locale_en_gb,
        plugin_filter,
        plugin_filter_defaults,
        plugin_filter_locale_en_gb,
        plugin_finetune,
        plugin_finetune_defaults,
        plugin_finetune_locale_en_gb,
        plugin_annotate,
        plugin_annotate_locale_en_gb,
        plugin_sticker,
        plugin_sticker_locale_en_gb,
        markup_editor_defaults,
        markup_editor_locale_en_gb,
      } = jQuery.fn.doka;

      setPlugins(plugin_crop, plugin_filter, plugin_finetune, plugin_annotate, plugin_sticker);
      // inline
      var ImageEditor = jQuery.fn.doka.openEditor({
        src: imgSRC,
        imageReader: createDefaultImageReader(),
        imageWriter: createDefaultImageWriter(),
        stickers: [["Emoji", ["⭐️", "😊", "👍", "👎", "☀️", "🌤", "🌥"]]],

        // set default view properties
        cropSelectPresetOptions: plugin_crop_defaults.cropSelectPresetOptions,
        filterFunctions: plugin_filter_defaults.filterFunctions,
        filterOptions: plugin_filter_defaults.filterOptions,
        finetuneControlConfiguration: plugin_finetune_defaults.finetuneControlConfiguration,
        finetuneOptions: plugin_finetune_defaults.finetuneOptions,

        markupEditorToolbar: markup_editor_defaults.markupEditorToolbar,
        markupEditorToolStyles: markup_editor_defaults.markupEditorToolStyles,
        markupEditorShapeStyleControls: markup_editor_defaults.markupEditorShapeStyleControls,

        // set locale to en_gb
        locale: Object.assign(
          {},
          locale_en_gb,
          plugin_crop_locale_en_gb,
          plugin_finetune_locale_en_gb,
          plugin_filter_locale_en_gb,
          plugin_annotate_locale_en_gb,
          plugin_sticker_locale_en_gb,
          markup_editor_locale_en_gb
        ),
      });

      // this will update the result image with the returned image file
      ImageEditor.on("process", (res) => self.saveEditedImage(res.dest));
    },
    saveEditedImage(theblob) {
      UIkit.notification("Saving");
      self = this;
      fd = new FormData();
      fd.append("ammended_image", theblob);
      fd.append("attachmentid", self.quickEdit.id);
      fd.append("security", a2020_content_ajax.security);
      fd.append("action", "a2020_save_edited_image");

      jQuery.ajax({
        url: a2020_content_ajax.ajax_url,
        type: "post",
        data: fd,
        async: true,
        cache: false,
        contentType: false,
        processData: false,
        success: function (response) {
          if (response) {
            data = JSON.parse(response);

            if (data.error) {
              UIkit.notification(data.error_message, "danger");
            } else {
              self.quickEdit.src = data.src;
              UIkit.notification(data.message, "success");
            }
          }
        },
        error: function (error) {
          console.log(error);
        },
      });
    },
    //////TITLE: SAVES ITEM FROM QUICK EDIT/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    updateItem() {
      self = this;
      options = self.quickEdit;

      jQuery.ajax({
        url: a2020_content_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_update_item",
          security: a2020_content_ajax.security,
          options: options,
        },
        success: function (response) {
          data = JSON.parse(response);
          if (data.error) {
            ///SOMETHING WENT WRONG
            UIkit.notification(data.error, { pos: "bottom-left", status: "danger" });
          } else {
            ///FOLDER MOVED
            UIkit.notification(data.message, { pos: "bottom-left", status: "success" });
            self.quickEdit.status = data.status;
            self.getFiles();
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
        this.moveContentToFolder(itemID, "toplevel");
      }
      jQuery("#a2020-folder-template").hide();
    },

    moveContentToFolder(contentID, destinationId) {
      self = this;

      jQuery.ajax({
        url: a2020_content_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_move_content_to_folder",
          security: a2020_content_ajax.security,
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
            self.getFiles();
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
        url: a2020_content_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_move_folder",
          security: a2020_content_ajax.security,
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
    //////TITLE: SELECTS ALL IN TABLE/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    selectAllTable() {
      self = this;
      if (self.contentTable.selectAll === false) {
        self.contentTable.selected = [];
        self.contentTable.content.forEach(function (item, index) {
          self.contentTable.selected.push(item.id);
        });
        self.contentTable.selectAll = true;
      } else {
        self.contentTable.selected = [];
        self.contentTable.selectAll = false;
      }
    },
    //////TITLE: SELECTS ALL IN TABLE/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    setView(view) {
      this.resetFilters();
      this.contentTable.views.currentView = view;

      for (var key in view.filters) {
        this.contentTable.filters[key] = view.filters[key];
      }
    },
    //////TITLE: Resets all filters/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    resetFilters() {
      this.contentTable.filters = {
        search: "",
        selectedPostTypes: [],
        selectedPostStatuses: [],
        selectedFileTypes: [],
        selectedCategories: [],
        selectedTags: [],
        date: "",
        dateComparison: "on",
        perPage: 20,
      };
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
    //////TITLE: checks total filters/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    totalFilters() {
      total = 0;
      total += this.contentTable.filters.selectedPostTypes.length;
      total += this.contentTable.filters.selectedPostStatuses.length;
      total += this.contentTable.filters.date.length;
      total += this.contentTable.filters.selectedFileTypes.length;
      total += this.contentTable.filters.selectedCategories.length;
      total += this.contentTable.filters.selectedTags.length;

      if (total > 0) {
        return true;
      } else {
        return false;
      }
    },
    //////TITLE: Saves current view/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    saveView() {
      self = this;
      count = this.contentTable.views.allViews.length + 1;

      newView = {
        name: this.newView.name,
        filters: this.contentTable.filters,
        id: count,
      };

      this.contentTable.views.allViews.push(newView);
      this.refreshViews();
    },
    //////TITLE: REFRESHES VIEWS/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    refreshViews() {
      allViews = this.contentTable.views.allViews;

      jQuery.ajax({
        url: a2020_content_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_save_view",
          security: a2020_content_ajax.security,
          allViews: allViews,
        },
        success: function (response) {
          data = JSON.parse(response);

          if (data) {
            self.loading = false;
            UIkit.notification(data.message, { pos: "bottom-left", status: "success" });
            UIkit.modal("#new-view-modal").hide();
            self.getFiles();
          }
        },
      });
    },
    //////TITLE: REMOVES VIEWS/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    removeView(option) {
      options = this.contentTable.views.allViews;
      newViews = [];
      options.forEach(function (item, index) {
        if (item.id != option.id) {
          newViews.push(item);
        }
      });
      this.contentTable.views.allViews = newViews;
      this.refreshViews();
    },

    duplicateItem(itemid) {
      this.contentTable.selected = [itemid];
      this.duplicateMultiple();
    },
    duplicateMultiple() {
      self = this;
      selected = this.contentTable.selected;

      jQuery.ajax({
        url: a2020_content_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_duplicate_selected",
          security: a2020_content_ajax.security,
          selected: selected,
        },
        success: function (response) {
          data = JSON.parse(response);

          if (data.error) {
            ///SOMETHING WENT WRONF
            UIkit.notification(data.error, { pos: "bottom-left", status: "danger" });
          } else {
            ///USER DELETED
            totaldeleted = parseInt(data.deleted_total);
            if (totaldeleted > 0) {
              UIkit.notification(data.deleted_total + " " + data.deleted_message, { pos: "bottom-left", status: "success" });
            }
            ///FAILED
            totalfailed = parseInt(data.failed_total);
            if (totalfailed > 0) {
              UIkit.notification(data.failed_total + " " + data.failed_message, { pos: "bottom-left", status: "warning" });
            }
            self.contentTable.selected = [];
            self.getFiles();
          }
        },
      });
    },

    batchUpdateTagsCats() {
      self = this;
      selected = this.contentTable.selected;

      jQuery.ajax({
        url: a2020_content_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_batch_tags_cats",
          security: a2020_content_ajax.security,
          selected: selected,
          theTags: self.batchUpdate,
        },
        success: function (response) {
          data = JSON.parse(response);

          if (data.error) {
            ///SOMETHING WENT WRONF
            UIkit.notification(data.error, { pos: "bottom-left", status: "danger" });
          } else {
            UIkit.notification(data.message, { pos: "bottom-left", status: "success" });
            self.contentTable.selected = [];
            UIkit.modal("#tags-cats-modal").hide();
            //self.getFiles();
          }
        },
      });
    },
    //////TITLE: DELETE SINGLE ITEM/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    deleteItem(itemid) {
      this.contentTable.selected = [itemid];
      this.deleteMultiple();
    },
    //////TITLE: DELETE SELECTED ITEMS/////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    deleteMultiple() {
      selected = this.contentTable.selected;

      UIkit.modal.confirm("Are you sure you want to delete " + selected.length + " items?").then(
        function () {
          self.deleteSelected();
        },
        function () {
          ///DON'T DELETE
        }
      );
    },
    deleteSelected() {
      self = this;
      selected = this.contentTable.selected;

      jQuery.ajax({
        url: a2020_content_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_delete_selected",
          security: a2020_content_ajax.security,
          selected: selected,
        },
        success: function (response) {
          data = JSON.parse(response);

          if (data.error) {
            ///SOMETHING WENT WRONF
            UIkit.notification(data.error, { pos: "bottom-left", status: "danger" });
          } else {
            ///USER DELETED
            totaldeleted = parseInt(data.deleted_total);
            if (totaldeleted > 0) {
              UIkit.notification(data.deleted_total + " " + data.deleted_message, { pos: "bottom-left", status: "success" });
            }
            ///FAILED
            totalfailed = parseInt(data.failed_total);
            if (totalfailed > 0) {
              UIkit.notification(data.failed_total + " " + data.failed_message, { pos: "bottom-left", status: "warning" });
            }
            self.contentTable.selected = [];
            self.getFiles();
          }
        },
      });
    },
  },
};

///BUILD VUE APP
const contentPageApp = a2020Vue.createApp(wpteams);

contentPageApp.component("multi-select", {
  data: function () {
    return {
      thisSearchInput: "",
    };
  },
  props: {
    options: Array,
    selected: Array,
    name: String,
    placeholder: String,
    single: Boolean,
  },
  methods: {
    //////TITLE: ADDS A SLECTED OPTION//////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////DESCRIPTION: ADDS A SELECTED OPTION FROM OPTIONS
    addSelected(option, options) {
      if (this.single == true) {
        options[0] = option;
      } else {
        options.push(option);
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
		  <span class="uk-text-meta">Select {{name}}...</span>\
		</span>\
		<span v-if="selected.length > 0" v-for="select in selected" class="selected-item">\
		  <template v-for="option in options">\
		   <div v-if="option.name == select">\
		   	{{option.label}}\
			<a class="uk-margin-small-left" href="#" @click="removeSelected(select,selected)">x</a>\
		   </div>\
		  </template>\
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

contentPageApp.component("a2020-checkbox", {
  props: {
    themodel: Array,
    thevalue: Number,
  },
  data: function () {
    return {};
  },
  methods: {
    //////TITLE: ADDS A SLECTED OPTION//////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////DESCRIPTION: ADDS A SELECTED OPTION FROM OPTIONS

    isOn() {
      return this.themodel.includes(this.thevalue);
    },
  },
  template:
    '{{themodel}}<div class="a2020-checkbox uk-border-rounded a2020-border all" :class="{\'checked\' : isOn()}" >\
      <span class="material-icons-outlined">done</span>\
      <input type="checkbox" v-model="themodel" :value="thevalue" style="opacity: 1 !important;">\
    </div>\
    ',
});

contentPageApp.component("folder-template", {
  props: {
    folder: Object,
    open: Array,
    current: Array,
  },
  data: function () {
    return {
      currentFolderObject: [],
      dragCounter: 0,
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
    },
    editFolder(theFolder) {
      this.$parent.editFolder(theFolder);
    },
    moveFolder(folderiD, destinationId) {
      self = this;
      this.dragCounter = 0;

      jQuery.ajax({
        url: a2020_content_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_move_folder",
          security: a2020_content_ajax.security,
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

      jQuery.ajax({
        url: a2020_content_ajax.ajax_url,
        type: "post",
        data: {
          action: "a2020_move_content_to_folder",
          security: a2020_content_ajax.security,
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
            self.$root.getFiles();
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
    '<div class="a2020-folder-component " :data-folder-id="folder.id">\
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

contentPageApp.mount("#a2020-content-app");
