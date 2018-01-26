<?php die; ?>

/*
// get browsenodes, search & sort params per category, per country 
// http://docs.aws.amazon.com/AWSECommerceService/latest/DG/LocaleUS.html

(function (window, document, $) {
  var $ww = $('#content-container #main-column #main #main-content #main-col-body .section .table'),
     $wwt = $ww.find('.title'),
     $wwc = $ww.find('.table-contents'),
     categs = [],
     LN = '\n', TAB = '\t';
  
  // get country
  var country = $wwt.text().split(' ')[0].toUpperCase();
  country = $.trim( country );
  //console.log( country );
  
  // get csv content
  $wwc.find('tbody tr').each(function (i) {
    var $this = $(this);
    var categ = {
      'title'  : '',
      'nodeid' : '',
      'sort'   : '',
      'search' : ''
    },
    categ2index = ['title', 'nodeid', 'sort', 'search'];
    
    $this.find('td').each(function(ii) {
      var $this2 = $(this);
      if (ii == 0) return true;
      var val = columnsParse( ii, $this2 );
      val = $.trim( val );
      
      categ[ categ2index[ii-1] ] = val;
      
    });
    
    // all categories special case
    if ( categ.title.toLowerCase() == 'all' ) {
      categ.title = 'AllCategories';
      categ.nodeid = '1';
    }

    categs.push( categ );
  });

  // generate php array
  LN = '';
  console.log(  "$assets['" + country + "'] = array(" );
  for (var i in categs) {
    var v = categs[i];
    if ( v.nodeid == '' || v.title == '' ) continue;
    
    console.log( TAB + "'" + v.title + "' => array(" );
    
    console.log( TAB+TAB + "'" + v.nodeid + "'," );
    console.log( TAB+TAB + "'" + v.search + "'," );
    console.log( TAB+TAB + "'" + v.sort + "'," );
    
    console.log( TAB + ")," );
    
    //console.log( v );
  }
  console.log( ");" );
  
  // get category parameters
  function columnsParse( index, column ) {
    
    // category title & nodeid
    if ( $.inArray(index, [1, 2]) > -1 ) {
      return $.trim( column.text() );
      
    }
    // category sort values & search parameters
    else if ( $.inArray(index, [3, 4]) > -1 ) {
      
      var tmp = [];
      column.find('p').each(function (i) {
        tmp.push( $.trim( $(this).text() ) );
      });
      return $.trim( tmp.join(',') );
    }
  }
 
})(window, document, jQuery, undefined);
*/


/*
// NOT WORKING ANYMORE - verified on 2016-07-10
// get amazon browsenode categories per countries
// http://docs.aws.amazon.com/AWSECommerceService/latest/DG/BrowseNodeIDs.html

(function (window, document, $, undefined) {
 var ww = $('.informaltable table'), tmp = [], resp = [];
  
  // get first csv row
  ww.find('thead tr th').each(function (i) {
    var $this = $(this);
    var title = $this.text();
    
    title = $.trim( title );
    tmp.push( title );
  });
  resp.push( tmp.join(',') );
  tmp = [];
  
  // get csv content
  ww.find('tbody tr').each(function (i) {
    var $this = $(this);
    
    tmp = [];
    $this.find('td').each(function(ii) {
      var $this2 = $(this);
      var val = $this2.text();
      val = $.trim( val );
      
      tmp.push( val );
    });
    resp.push( tmp.join(',') );

  });

  // generate csv file
  for (var i in resp) {
    var v = resp[i];
    
    console.log( v );
  }
 
})(window, document, jQuery);
*/