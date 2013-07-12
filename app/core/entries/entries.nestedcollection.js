define([
  "app",
  "backbone",
  "core/collection",
  "core/entries/entries.collection"
],

function(app, Backbone, Collection, EntriesCollection) {

  //@todo: Try merging this with entries.collection.js
  var NestedCollection = Collection.extend({

    isNested: true,

    model: Backbone.Model.extend({

      isNested: true,

      parse: function(result) {
        result.data = new this.collection.nestedCollection.model(result.data, {collection: this.collection.nestedCollection});
        this.collection.nestedCollection.add(result.data);
        return result;
      },

      //DRY this up please and move it to BB's protoype
      toJSON: function(options) {
        var attributes = _.clone(this.attributes);
        attributes.data = this.get('data').toJSON();
        return attributes;
      }
    }),

    trash: [],

    create: function() {
      return this.nestedCollection.create(arguments);
    },

    remove: function(model, options) {
      if (!model.isNew()) {
        this.trash.push(model);
      }
      this.constructor.__super__.remove.apply(this, arguments);
    },

    //If getNested is set to true, the this will point to the nested element
    get: function(id, getNested) {
      var model = NestedCollection.__super__.get.call(this, id);
      if (getNested) model = model.get('data');
      return model;
    },

    add: function(models, options) {
      if (options && options.nest) {
        if (!_.isArray(models)) { models = [models]; }
        models = _.map(models, function(model) {
          var obj = {};
          obj.data = model;
          return obj;
        });
      }
      NestedCollection.__super__.add.apply(this, [models, options]);
    },

    getModels: function() {
      return this.filter(function(model) {
        return !(model.has('active') && model.get('active') === 0);
      });
    },

    getColumns: function() {
      return this.nestedCollection.getColumns();
    },

    parse: function(response) {
      return (response.rows === undefined) ? response : response.rows;
    },

    hasColumn: function(columnName) {
      return this.structure.get(columnName) !== undefined;
    },

    initialize: function(models, options) {

      EntriesCollection = EntriesCollection || require('core/entries/entries.collection');

      this.structure = options.structure;
      this.table = options.table;
      this.preferences = options.preferences;
      this.filters = options.filters;
      this.junctionStructure = options.junctionStructure;

      if (this.table.id === 'directus_media') {
        this.droppable = true;
        options.url = app.API_URL + 'media';
      }
      this.nestedCollection = new EntriesCollection({}, options);
      this.nestedCollection.on('change', function() {
        this.trigger('change');
      }, this);
    }

  });

  return NestedCollection;
});