<!DOCTYPE html>
<html lang="en">
 <head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="title" content="CC Checker - Free credit card checker namso-gen.eu.org" />
  <meta name="description" content="cc checker for free credit card numbers." />
  <meta name="keywords" content="cc,checker,mass cc checker,bin,generated cc,credit card,live,dead" />
  <meta name="robots" content="index, follow" />
  <meta name="language" content="English" />
  <meta name="author" content="Saksham" />
  <meta property="og:url" content="https://namso-gen.eu.org" />
  <meta property="og:image" content="https://rawcdn.githack.com/OshekharO/Entertainment-Index/17d005915d5e20780a46aef227f08367ca8efb3a/img/apple-touch-icon.png" />
  <meta property="og:locale" content="en_US" />
  <meta property="og:type" content="website" />
  <meta name="copyright" content="Copyright © 2023 OshekharO" />
  <meta property="og:image" content="https://rawcdn.githack.com/OshekharO/Entertainment-Index/17d005915d5e20780a46aef227f08367ca8efb3a/img/android-chrome-512x512.png" />
  <link rel="shortcut icon" href="https://rawcdn.githack.com/OshekharO/Entertainment-Index/17d005915d5e20780a46aef227f08367ca8efb3a/img/favicon.ico" type="image/x-icon" />
  <link rel="apple-touch-icon" sizes="180x180" href="https://rawcdn.githack.com/OshekharO/Entertainment-Index/17d005915d5e20780a46aef227f08367ca8efb3a/img/apple-touch-icon.png" />
  <link rel="icon" type="image/png" href="https://rawcdn.githack.com/OshekharO/Entertainment-Index/17d005915d5e20780a46aef227f08367ca8efb3a/img/favicon-32x32.png" sizes="32x32" />
  <link rel="icon" type="image/png" href="https://github.com/OshekharO/Entertainment-Index/blob/master/img/favicon-16x16.png" sizes="16x16" />
  <title>CHECKER CC</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous" />
  <link href="style.css" rel="stylesheet" />
 </head>

 <body>
  <div class="fs-3 fw-bold mb-5 text-uppercase mx-auto text-center text-light">Credit Card Checker</div>
  <form method="post" action="api.php" role="form" id="form">
   <div class="box-body">
    <div class="box-content">
     <label for="cc" class="form-label fs-6 font-monospace badge bg-danger text-light">Card Numbers</label>
     <div>
      <textarea class="form-control" rows="10" id="cc" name="cc" title="53012724539xxxxx|05|2022|653" placeholder="53012724539xxxxx|05|2022|653" required></textarea>
     </div>
     <div class="button text-center mb-3 mt-3">
      <button type="submit" name="valid" class="btn btn-outline-success text-light">START</button>
      <button type="button" id="stop" class="btn btn-outline-danger text-light" disabled>STOP</button>
     </div>
    </div>
   </div>

   <!-- Info success -->
   <div class="box-title">
    <h3 class="panel-title alert alert-primary font-monospace">Live - <span class="badge bg-success live">0</span></h3>
   </div>
   <div class="box-body">
    <div class="box-content alert alert-success">
     <div class="panel-body success"></div>
    </div>
   </div>

   <!-- Info error -->
   <div class="box-title">
    <h3 class="panel-title alert alert-primary font-monospace">Die - <span class="badge bg-danger die">0</span></h3>
   </div>
   <div class="box-body">
    <div class="box-content alert alert-danger">
     <div class="panel-body danger"></div>
    </div>
   </div>
   
    <!-- Info unknown -->
      <div class="box-title">
      <h3 class="panel-title alert alert-primary font-monospace">Unknown - <span class="badge bg-warning unknown">0</span></h3>
      </div>
      <div class="box-body">
        <div class="box-content alert alert-warning">
          <div class="panel-body warning"></div>
        </div>
      </div>
  </form>

  <script src="https://code.jquery.com/jquery-3.6.3.min.js" integrity="sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU=" crossorigin="anonymous"></script>

<script type="text/javascript">
$(document).ready(function() {
    $("button[name='valid']").attr("disabled", false);
    $("button[id='stop']").attr("disabled", true);
    var intervalId;
    $("#form").submit(function(event) {
        event.preventDefault();
        event.stopPropagation();
        var form = $(this);
        var cardNumbers = $("#cc").val().split("\n");
        if (cardNumbers != "" || typeof cardNumbers != "object") {
            var i = 0,
                live = 0 + $(".live").text(),
                die = 0 + $(".die").text(),
                unknown = 0 + $(".unknown").text(),
                total = cardNumbers.length;
            intervalId = setInterval(function() {
                $.post(form.attr("action"), {"data": cardNumbers[i]}, function(response, status) {
                    if (status == "success") {
                        var result = $.parseJSON(response);
                        if (result.error == 1) {
                            $(".success").prepend(result.msg);
                            live++;
                            $(".live").text(live);
                        } else if (result.error == 2) {
                            $(".danger").prepend(result.msg);
                            die++;
                            $(".die").text(die);
                        } else if (result.error == 3) {
                            $(".warning").prepend(result.msg);
                            unknown++;
                            $(".unknown").text(unknown);
                        } else if (result.error == 4) {
                            $(".info").show().prepend(result.msg + "<br>");
                        }
                    }
                });
                if (i == total) {
                    clearInterval(intervalId);
                    $("#cc").val("");
                    $("#cc").attr("disabled", false);
                    $("button[name='valid']").attr("disabled", false);
                    $("button[id='stop']").attr("disabled", true);
                } else {
                    i++;
                    $("#cc").attr("disabled", true);
                    $("button[id='stop']").attr("disabled", false);
                    $("button[name='valid']").attr("disabled", true);
                }
            }, 1500);
        } else {
            $(".info").show().html("<b>Error</b>");
        };
        return false;
    });
    $("#stop").click(function() {
        clearInterval(intervalId);
        $("#cc").attr("disabled", false);
        $("button[name='valid']").attr("disabled", false);
        $("button[id='stop']").attr("disabled", true);
    });
});
</script>
</body>
</html>
