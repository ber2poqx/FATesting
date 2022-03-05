$("#area").change(function (){
    var name = $("#area").val();
    $.ajax({
    url: "myCustom.php",
    type: "POST",
    dataType: 'json',
    cache: false,
    data : { type: 'areaSelect', name:name },
        success: function(response){
        	var name = response.name;
        	$('#collectors_name').val(name);
            //document.getElementById("collectors_name").disabled = true;
        }
    });
});

$("#collectors_name").change(function (){
    var names = $("#collectors_name").val();
    $.ajax({
    url: "myCustom.php",
    type: "POST",
    dataType: 'json',
    cache: false,
    data : { type: 'collectorSelect', names:names },
        success: function(response){
        	var names = response.names;
        	$('#area').val(names);
        }
    });

});


$("#municipality").change(function (){
    var mcode = $("#municipality").val();
    $.ajax({
    url: "myCustom.php",
    type: "POST",
    dataType: 'json',
    cache: false,
    data : { type: 'munizipcodeSelect', mcode:mcode },
        success: function(response){
            var mcode = response.mcode;
            $('#zip_code').val(mcode);
        }
    });

});