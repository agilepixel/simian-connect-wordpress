(function () {
    var DOM = tinymce.DOM;
    tinymce.create('tinymce.plugins.SimianConnect', {
        /**
         * Initializes the plugin, this will be executed after the plugin has been created.
         * This call is done before the editor instance has finished it's initialization so use the onInit event
         * of the editor instance to intercept that event.
         *
         * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
         * @param {string} url Absolute URL to where the plugin is located.
         */
        init: function (ed, url) {

            // Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceExample');
            ed.addCommand('go_simian', function () {
                var rid = prompt("What is the reel ID?", "");
                tinyMCE.execCommand('mceInsertContent', false, '[swebreel id=' + rid + ']', {
                    format: 'raw'
                });
                //ed.windowManager.open({
                //       file : url + '/dialog.htm',
                //       width : 320 + ed.getLang('example.delta_width', 0),
                //        height : 120 + ed.getLang('example.delta_height', 0),
                //        inline : 1
                // }, {
                //        plugin_url : url, // Plugin absolute URL
                //         some_custom_arg : 'custom arg' // Custom argument
                // });
            });

            ed.addCommand('go_simian2', function () {
                //tb_show('Test', 'media-upload.php?type=image&TB_iframe=1');
                tb_show('Select a reel...', ajaxurl + '?action=simian_select_reel&height=652&width=640');
            });

            // Register example button
            ed.addButton('simianc', {
                title: 'Simian Connect',
                cmd: 'go_simian2',
                image: url + '/../media/simian-icon-16.png'
            });


        },

        /**
         * Creates control instances based in the incomming name. This method is normally not
         * needed since the addButton method of the tinymce.Editor class is a more easy way of adding buttons
         * but you sometimes need to create more complex controls like listboxes, split buttons etc then this
         * method can be used to create those.
         *
         * @param {String} n Name of the control to create.
         * @param {tinymce.ControlManager} cm Control manager to use inorder to create new control.
         * @return {tinymce.ui.Control} New control instance or null if no control was created.
         */
        createControl: function (n, cm) {
            return null;
        },

        /**
         * Returns information about the plugin as a name/value array.
         * The current keys are longname, author, authorurl, infourl and version.
         *
         * @return {Object} Name/value array containing information about the plugin.
         */
        getInfo: function () {
            return {
                longname: 'Simian plugin',
                author: 'Agile Pixel',
                authorurl: 'http://agilepixel.io',
                infourl: 'http://agilepixel.io',
                version: "0.5"
            };
        }
    });

    // Register plugin
    tinymce.PluginManager.add('simianc', tinymce.plugins.SimianConnect);
})();
