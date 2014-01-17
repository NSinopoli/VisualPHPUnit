(function($) {

  $.extend($.fn, {
    fileSelector: function(options) {
      options = $.extend({
        callback: function() {},
        collapseSpeed: 500,
        expandSpeed: 500,
        roots: ['/'],
        serverEndpoint: '/'
      }, options);

      return this.each(function() {
	
        function buildTree($fileSelector, dir, isActive) {
          $.get(options.serverEndpoint, {dir: dir}, function(response) {
	    if(typeof dir['path'] !== 'undefined')
            {
                suite = dir;
                dir = suite['path'];

                rootDirs = new Array();
                for(s in options.roots)
                {
                    rootDirs.push(s['path']);
                }

                html  = "<ul class='nav-list nav'>";
                html += "<li class='suite' id='"+suite['suite']+"'>";
                html += "<a href='#' data-path='"+suite['path']+"'><i class='icon-folder-close'></i>"+suite['name']+"</a>";
                html += "</li>";
                html += "</ul>";

                var $suite = $(html);

                $fileSelector.append($suite);
                innerFiles = $fileSelector.find('#'+suite['suite']);
            }
            else
            {
                innerFiles = $fileSelector;
                rootDirs = options.roots;
            }
            var classAttr = ( $.inArray(dir, rootDirs) ) ? " nav" : '',
                html = "<ul class='nav-list" + classAttr + "' " +
                  "style='display: none;'>";

            response = $.parseJSON(response);

            $.each(response, function(index, file) {
              var icon = ( file.type == 'directory' )
                ? 'icon-folder-close'
                : 'icon-file';
              var classAttr = ( isActive ) ? ' active' : '';

              html += "<li class='" + file.type + classAttr + "'>" +
                        "<a href='#' data-path='" + file.path + "'>" +
                          "<i class='" + icon + "'></i>" +
                          file.name +
                        '</a>' +
                      '</li>';
            });

            html += '</ul>';
            var $ul = $(html);
            innerFiles.append($ul);

            if ( $.inArray(dir, rootDirs) ) {
              innerFiles.find('ul:hidden').show();
            } else {
              innerFiles.find('ul:hidden').slideDown(options.expandSpeed);
            }

            if(typeof $suite !== 'undefined')
            {
                bindTree($suite);
            }
            else
            {
                bindTree($ul);
            }
          });
        }

        function bindTree($fileSelector) {
          $fileSelector.find('li a').bind('click', function(event) {
            var $this = $(this),
                $parent = $this.parent(),
                $children = $this.children(),
                selector,
                $nearest;

            event.preventDefault();

            if ( $parent.hasClass('directory') || $parent.hasClass('suite') ) {
              if ( event.metaKey || event.ctrlKey ) {
                $parent.toggleClass('active');
                $parent.find('li').toggleClass('active');
                options.callback($this.attr('data-path'));
              } else {
                if ( $children.hasClass('icon-folder-close') ) {
                  $parent.find('ul').remove();
                  buildTree(
                    $parent,
                    encodeURIComponent($this.attr('data-path')),
                    $parent.hasClass('active')
                  );
                  $children.removeClass().addClass('icon-folder-open');
                } else {
                  $parent.find('ul').slideUp(options.collapseSpeed);
                  $children.removeClass().addClass('icon-folder-close');
                }
              }
            } else {
              if ( event.shiftKey ) {
                selector = ( $parent.hasClass('active') )
                  ? ':not(.active)'
                  : '.active';

                if ( $nearest = $parent.siblings(selector) ) {
                  if ( $nearest.index() > $parent.index() ) {
                    $parent.nextUntil(selector).toggleClass('active');
                  } else {
                    $parent.prevUntil(selector).toggleClass('active');
                  }
                }
              }

              $parent.toggleClass('active');
              options.callback($this.attr('data-path'));
            }

          });
        }

        //var length = options.roots.length;
        var $self = $(this);
        for(suite in options.roots){
            buildTree($self, options.roots[suite]);
        }
      });
    }
  });

})(jQuery);
