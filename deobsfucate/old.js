$(document).ready(function() {
    // Enables the button with the name "valid" and disables the button with the id "stop"
    $("button[name='valid']").attr("disabled",false);
    $("button[id='stop']").attr("disabled",true);

    var intervalId;
    
    $("#form").submit(function(event){
        event.preventDefault();
        event.stopImmediatePropagation();

        var form = $(this);
        var inputLines = $("#cc").val().split("\n");

        if(inputLines != "" || typeof inputLines != "object"){
            var index = 0,
                liveCount = 0 + $(".live").text(),
                dieCount = 0 + $(".die").text(),
                unknownCount = 0 + $(".unknown").text(),
                linesCount = inputLines.length;
                
            intervalId = setInterval(function(){
                $.post(form.attr("action"), {"data":inputLines[index]}, function(response, textStatus){
                    if(textStatus == "success"){
                        var parsedResponse = JSON.parse(response);
                        if(parsedResponse.error == 1){
                            $(".success").prepend(parsedResponse.msg);
                            liveCount++;
                            $(".live").text(liveCount);
                        } else if(parsedResponse.error == 2){
                            $(".danger").prepend(parsedResponse.msg);
                            dieCount++;
                            $(".die").text(dieCount);
                        } else if(parsedResponse.error == 3){
                            $(".warning").prepend(parsedResponse.msg);
                            unknownCount++;
                            $(".unknown").text(unknownCount);
                        } else if(parsedResponse.error == 4){
                            $(".info").show().prepend(parsedResponse.msg + "<br>");
                        }
                    }
                });
                
                if(index == linesCount){
                    clearInterval(intervalId);
                    $("#cc").val("");
                    $("#cc").attr("disabled",false);
                    $("button[name='valid']").attr("disabled",false);
                    $("button[id='stop']").attr("disabled",true);
                } else {
                    index++;
                    $("#cc").attr("disabled",true);
                    $("button[id='stop']").attr("disabled",false);
                    $("button[name='valid']").attr("disabled",true);
                }
            },1500);
        } else {
            $(".info").show().html("<b>Error</b>");
        }

        return false;
    });

    // Cancels the operation when the stop button is clicked
    $("#stop").click(function(){
        clearInterval(intervalId);
        $("#cc").attr("disabled",false);
        $("button[name='valid']").attr("disabled",false);
        $("button[id='stop']").attr("disabled",true);
    })
});
