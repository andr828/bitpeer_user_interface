$(document).ready(function (){

    // Removes address bar on mobiles and tablets
    window.addEventListener("load", function() {
        setTimeout(function(){
            window.scrollTo(0, 0);
        });
    });

    // Show / hide sidebar
    $('.quicknav').click(function () {

        // Toggle sidebar
        $('#sidebar').toggle();
        $('#content').toggleClass('content-mobile');

        if ($('#sidebar').is(":hidden")) {
            $('#header').css("position", 'static');
            $('#content').css("padding-top", '0');
        } else {
            $('#header').css("position", 'fixed');
            $('#content').css("padding-top", '70px');
        }

    });

    // Retina friendly images
    if (window.devicePixelRatio == 2) {

        var images = $("img.2x");

        // Loop through the images and make them hi-res
        for (var i = 0; i < images.length; i++) {

            // Get new image name for @2x version
            var imageType = images[i].src.substr(-4);
            var imageName = images[i].src.substr(0, images[i].src.length - 4);
            imageName += "@2x" + imageType;

            // Change image source
            images[i].src = imageName;

        }

    }

});
