$(document).ready(function () {
  var pathSeparator = " → ";
  var optionsDefJson = '{JSON_DEF}';
  var columnDefJson = '{JSON_COL}';
  var selectText = "{TXT_SEL}";
  var levels = {NUM_LEVELS};
  var id = "{POST_VAR}";

  var optionsDef = $.parseJSON(optionsDefJson);
  var columnDef = $.parseJSON(columnDefJson);


  function setOptionsForSelection(input, subOptions, level)
  {
    var currentElement = input.shift();

    if(currentElement !== undefined) {
      currentElement = currentElement.trim();
    }

    console.log(columnDef);
    console.log(level);

    if(typeof subOptions.options !== 'undefined' && subOptions.options.length === 0 && typeof columnDef[level] !== 'undefined') {
      var defaultValue = {'name' : columnDef[level]['default']}
      subOptions.options.push(defaultValue);
    }

    $.each(subOptions.options, function (i, option) {
      var selectOption = new Option(option.name,option.name);
      $('#' + id + '_' + level).append(selectOption);
      if(currentElement === option.name.trim()) {
        selectOption.selected = true;
        setOptionsForSelection(input, option, level + 1);
      }

    });
  }

  function loadOptionsFromHiddenValue()
  {

    var value = $('#' + id + '_hidden').val();
    var optionsPerLevel = value.split(pathSeparator);
    setOptionsForSelection(optionsPerLevel, optionsDef, 0);
  }

  function writeOptionsToHiddenValue()
  {

    var hidden = [];
    for(i=0;i<levels;i++) {
      selectedText = $('#' + id + '_' + i).val() || '';
      if(selectedText.length > 0) {
        hidden[i] = selectedText.trim();
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
        header.show();
        select.show();
      }
    }
  }

  loadOptionsFromHiddenValue();

  changeSelectVisibility(id, levels);

  for(i = 0; i < levels; i++) {
    (function(i) {
      $('#'+id+'_'+i).change(function()
      {
        for(j = i+1; j < levels; j++) {
          $('#' + id + '_' + j).children().remove();
        }
        writeOptionsToHiddenValue();

        for(i = 0; i < levels; i++) {
          $('#' + id + '_' + i).children().remove();
          selectOption = new Option(selectText,'');
          $('#' + id + '_' + i).append(selectOption)
        }
        loadOptionsFromHiddenValue();

        changeSelectVisibility(id, levels);
      });
    })(i);
  }
});
