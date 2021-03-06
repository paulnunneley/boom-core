(function(Backbone, BoomCMS) {
    'use strict';

    BoomCMS.Collections.People = Backbone.Collection.extend({
        model: BoomCMS.Person,
        url: BoomCMS.urlRoot + 'person',
        comparator: 'name'
    });
}(Backbone, BoomCMS));
