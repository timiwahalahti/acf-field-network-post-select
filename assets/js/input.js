/*
* @Author: Timi Wahalahti
* @Date:   2020-10-01 12:56:23
* @Last Modified by:   Timi Wahalahti
* @Last Modified time: 2020-10-01 17:16:16
*/

(function($){

  var Field = acf.models.SelectField.extend({
    type: 'network_post_select',
  });

  acf.registerFieldType( Field );

})(jQuery);
