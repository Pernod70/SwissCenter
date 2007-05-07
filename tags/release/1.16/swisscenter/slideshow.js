// JScript source code
var imgElement;
var imgSlides;
var imgCurrentIndex;
var imgLoop;
var imgDelay;

function Slideshow(delay, image, slides, loop)
{
  imgElement = image;
  imgSlides = slides;
  imgCurrentIndex = 0;
  imgLoop = loop;
  imgDelay = delay;
    
  ChangeSlide();
}

function ChangeSlide(image, slides, index)
{
  imgElement.src = imgSlides[imgCurrentIndex++];
    
  if((imgCurrentIndex >= imgSlides.length) && imgLoop)
      imgCurrentIndex = 0;
        
  if(imgCurrentIndex < imgSlides.length)
      setTimeout("ChangeSlide()", imgDelay * 1000);
}
