jQuery(document).ready(function() {
    if (JSINFO.ACT === "admin") {return;}
    jQuery('.plugin_caption_figure').each(function(i,e) {
        let figureElem = jQuery(this).find("img").first();
        let figureCaptionElem = jQuery(this).find("figcaption").first();
        
        getImageSize(figureElem, function(width, height){
            figureElemWidth = width;
            if (figureElem.attr("width") !== 0 && figureElem.attr("width") !== undefined && figureElem.attr("width") !== false) {
                figureElemWidth = figureElem.attr('width');
            }
            
            let figureClassList = figureElem.attr('class').split(/\s+/);
            jQuery(figureClassList).each(function(index){
                switch(figureClassList[index]) {
                    case 'medialeft':
                    figureCaptionElem.addClass("medialeft");
                    figureCaptionElem.css("width", figureElemWidth);
                    break;
                    case 'mediaright':
                    figureCaptionElem.addClass("mediaright");
                    figureCaptionElem.css("width", figureElemWidth);
                    break;
                }
            });
        });
    });
});

function getImageSize(img, callback){
    img = jQuery(img);
    
    var wait = setInterval(function(){        
        var w = img.width(),
        h = img.height();
        
        if(w && h){
            done(w, h);
        }
    }, 0);
    
    var onLoad;
    img.on('load', onLoad = function(){
        done(img.width(), img.height());
    });
    
    
    var isDone = false;
    function done(){
        if(isDone){
            return;
        }
        isDone = true;
        
        clearInterval(wait);
        img.off('load', onLoad);
        
        callback.apply(this, arguments);
    }
}
