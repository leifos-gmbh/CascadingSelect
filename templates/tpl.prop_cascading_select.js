$(document).ready(function () {
  var pathSeparator = " → ";
  var innerSeparator = " ↕ ";
  var optionsDefJson = '{JSON_DEF}';
  var columnDefJson = '{JSON_COL}';
  var selectText = "{TXT_SEL}";
  var levels = {NUM_LEVELS};
  var id = "{POST_VAR}";

  var curroptions = [];
  var noopt = "{NOOPT}";
  var optionsDef = $.parseJSON(optionsDefJson);
  var columnDef = $.parseJSON(columnDefJson);


  function setOptionsForSelection(input, subOptions, level)
  {
    var currentElement = input.shift();

    if(currentElement !== undefined) {
      currentElement = currentElement.trim();
      var curr = currentElement.split(innerSeparator)[0];
    }

    curroptions[level] = 0;

    if(typeof subOptions.options !== 'undefined' && subOptions.options.length === 0 && typeof columnDef[level] !== 'undefined') {
      var defaultValue = {'name' : noopt}
      subOptions.options.push(defaultValue);
    }

    $.each(subOptions.options, function (i, option) {
      var selectOption = new Option(option.name,option.name);
      $('#' + id + '_' + level).append(selectOption);
      var opt = option.name.trim();
      curroptions[level]++;
      if(curr === opt.split(innerSeparator)[0] || option.name.trim() === noopt) {
        selectOption.selected = true;
        if (level+1 < levels) {
          setOptionsForSelection(input, option, level + 1);
        }
      }
    });
  }

  function loadOptionsFromHiddenValue()
  {
    var value = $('#' + id + '_hidden').val();
    var optsPerLevel = value.split(pathSeparator);
    for (i=0;i<levels;i++) {
      curroptions[i]=0;
    }
    setOptionsForSelection(optsPerLevel, optionsDef, 0);
  }

  function writeOptionsToHiddenValue()
  {

    var hidden = [];
    for(i=0;i<levels;i++) {
      selectedText = $('#' + id + '_' + i).val() || '';
      if(selectedText.length > 0) {
        hidden[i] = selectedText.trim();
      }
      if (curroptions[i] == 0) {
        hidden[i] = columnDef[i]['default'];
      }
    }
    $("#" + id + '_hidden').val(hidden.join(pathSeparator));
  }

  function changeSelectVisibility(id, levels)
  {
    for(n = 0; n < levels; n++) {
      var select = $('#' + id + '_' + n);
      // Use the select to find the header and then turn it back into a jquery object
      var header = $(select.closest('tbody').children('tr[class="std"]').children()[n]);
      if(select.children().length === 1 ) {
        header.hide();
        select.hide();
      } else {
        if (select.children()[1].childNodes[0].nodeValue == noopt) {
          header.hide();
        } else {
          header.show();
        }
        select.show();
      }
    }
  }

  //main
  //initial display
  loadOptionsFromHiddenValue();

  changeSelectVisibility(id, levels);

  //reactions for clicks
  for(i = 0; i < levels; i++) {
    (function(i) {
      $('#'+id+'_'+i).change(function()
      {
        for(j = i+1; j < levels; j++) {
          $('#' + id + '_' + j).children().remove();
        }
        //determine values to be returned
        writeOptionsToHiddenValue();

        //prepare view of changes
        for(i = 0; i < levels; i++) {
          $('#' + id + '_' + i).children().remove();
          selectOption = new Option(selectText,'');
          $('#' + id + '_' + i).append(selectOption)
        }
        loadOptionsFromHiddenValue();

        writeOptionsToHiddenValue();
        changeSelectVisibility(id, levels);
      });
    })(i);
  }
});
