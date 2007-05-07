/*
        Unobtrusive Slider Control by frequency decoder v1.3 (http://www.frequency-decoder.com/)

        Released under a creative commons Attribution-ShareAlike 2.5 license (http://creativecommons.org/licenses/by-sa/2.5/)

        You are free:

        * to copy, distribute, display, and perform the work
        * to make derivative works
        * to make commercial use of the work

        Under the following conditions:

                by Attribution.
                --------------
                You must attribute the work in the manner specified by the author or licensor.

                sa
                --
                Share Alike. If you alter, transform, or build upon this work, you may distribute the resulting work only under a license identical to this one.

        * For any reuse or distribution, you must make clear to others the license terms of this work.
        * Any of these conditions can be waived if you get permission from the copyright holder.
*/

var fdSliderController;

(function() {

        function fdSlider(inp,range,callback,classname,hide,tween, vertical) {
                this._created   = false;
                this._inp       = inp;
                this._hideInput = hide||false;
                this._min       = range[0]||0;
                this._max       = range[1]||0;
                this._range     = this._max - this._min;

                this._tween     = tween;
                this._mouseX    = 0;
                this._timer     = null;
                this._classname = classname||'';
                this._drag      = false;
                this._kbEnabled = true;
                this._callback  = callback||false;
                this._vertical  = vertical||false;
                this._x;
                this._y;
                this._wrapper;
                this._outerWrapper;
                this._bar;
                this._handle;

                this._events = {
                        stopevent: function(e) {
                                if (e == null) e = document.parentWindow.event;

                                if (e.stopPropagation) {
                                        e.stopPropagation();
                                        e.preventDefault();
                                }
                                /*@cc_on@*/
                                /*@if(@_win32)
                                e.cancelBubble = true;
                                e.returnValue = false;
                                /*@end@*/
                                return false;
                        },
                        onfocus: function(e) {
                                self._wrapper.className = self._wrapper.className + ' focused';
                        },
                        onblur: function(e) {
                                self._wrapper.className = self._wrapper.className.replace('focused','');
                        },
                        onkeypress: function(e) {
                                if (e == null) e = document.parentWindow.event;
                                var kc = e.keyCode;
                                if ((kc >= 35 && kc <= 40) || !self._kbEnabled) {
                                        return self._events.stopevent(e);
                                }
                        },
                        onkeydown: function(e) {
                                if(!self._kbEnabled) return true;

                                if (e == null) e = document.parentWindow.event;
                                var kc = e.keyCode != null ? e.keyCode : e.charCode;

                                if ( kc < 35 || kc > 40 ) return true;

                                var xtmp = self._vertical ? self._handle.offsetTop : self._handle.offsetLeft;
                                var wtmp = self._vertical ? self._outerWrapper.offsetHeight - self._handle.offsetHeight : self._outerWrapper.offsetWidth - self._handle.offsetWidth;
                                var inc  = Math.round(self._incPx / 2) < 1 ? 1 : Math.round(self._incPx / 2);

                                if(self._vertical) inc = -inc;
                                
                                if ( kc == 37 || kc == 40 ) {
                                        // left, up
                                        xtmp -= inc;
                                        xtmp = Math.floor(xtmp);
                                } else if ( kc == 39 || kc == 38) {
                                        // right, down
                                        xtmp += inc;
                                        xtmp = Math.ceil(xtmp);
                                } else if ( kc == 35 ) {
                                        // max
                                        xtmp = wtmp;
                                } else if ( kc == 36 ) {
                                        // min
                                        xtmp = 0;
                                }

                                if(xtmp < 0) xtmp = 0;
                                else if(xtmp > wtmp) xtmp = wtmp;
                                
                                self.updateInput(xtmp);
                                if(self._vertical) self._handle.style.top = xtmp + "px";
                                else self._handle.style.left = xtmp + "px";
                                return self._events.stopevent(e);
                        },
                        onchange: function( e ) {
                                var pos = self.calcFromInput();
                                if(self._vertical) self._handle.style.top = pos + "px";
                                else self._handle.style.left = pos + "px";

                                if(self._callback && self._callback in window) {
                                        window[self._callback]();
                                }
                                return true;
                        },
                        onmouseover: function( e ) {
                                this.className = this.className +' fd-slider-hover';
                        },
                        onmouseout: function( e ) {
                                this.className = this.className.replace(/fd\-slider\-hover/g,"");
                        },
                        onmouseup: function( e ) {
                                self._kbEnabled = true;
                                self._handle.className = self._handle.className.replace("fd-slider-hover","");
                                if(self._drag) {
                                        fdSliderController.removeEvent(document, 'mousemove', self._events.trackmouse);
                                        self._drag = false;
                                }
                                fdSliderController.removeEvent(document, 'mouseup',   self._events.onmouseup);

                                clearTimeout(self._timer);
                                self._timer = null;
                        },
                        trackmouse: function( e ) {
                                if (!e) var e = window.event;
                                var x = self._vertical ? self._handleX + (e.clientY-self._mouseX) : self._handleX + (e.clientX-self._mouseX);
                                if(x < 0) x = 0;
                                var max = self._vertical ? self._outerWrapper.offsetHeight - self._handle.offsetHeight : self._outerWrapper.offsetWidth - self._handle.offsetWidth;
                                if(x > max) x = max;
                                if(self._vertical) self._handle.style.top = x + "px";
                                else self._handle.style.left = x + "px";
                                self.updateInput(x);
                        },
                        onmousedown: function( e ) {
                                /*@cc_on@*/
                                /*@if(@_win32)
                                self._wrapper.focus();
                                /*@end@*/

                                clearTimeout(self._timer);
                                self._timer = null;
                                self._kbEnabled = false;

                                var targ;
                                if (!e) var e = window.event;

                                if (e.target) targ = e.target;
                                else if (e.srcElement) targ = e.srcElement;

                                if (targ.nodeType == 3) targ = targ.parentNode;

                                if(targ.className.search("fd-slider-handle") != -1) {
                                        self._handle.className = self._handle.className + " fd-slider-hover";
                                        self._mouseX = self._vertical ? e.clientY : e.clientX;
                                        self._handleX = parseInt(self._vertical ? self._handle.style.top : self._handle.style.left)||0;
                                        self._handle.className = self._handle.className.replace('fd-slider-hover', '') + ' fd-slider-hover';
                                        self._drag = true;

                                        fdSliderController.addEvent(document, 'mousemove', self._events.trackmouse);
                                        fdSliderController.addEvent(document, 'mouseup',   self._events.onmouseup);
                                } else {
                                        self.locate();
                                        self._drag      = false;
                                        var posx        = 0;
                                        var sLft        = 0;
                                        var sTop        = 0;

                                        // Internet Explorer doctype woes
                                        if (document.documentElement && document.documentElement.scrollTop) {
                                                sTop = document.documentElement.scrollTop;
                                                sLft = document.documentElement.scrollLeft;
                                        } else if (document.body) {
                                                sTop = document.body.scrollTop;
                                                sLft = document.body.scrollLeft;
                                        }

                                        if (e.pageX)            posx = self._vertical ? e.pageY : e.pageX;
                                        else if (e.clientX)     posx = self._vertical ? e.clientY + sTop : e.clientX + sLft;

                                        posx -= ((self._vertical ? self._y : self._x) + Math.round((self._vertical ? self._handle.offsetHeight : self._handle.offsetWidth)/2));

                                        if(posx < 0) posx = 0;
                                        else if(!self._vertical && posx > self._outerWrapper.offsetWidth - self._handle.offsetWidth)  posx = self._outerWrapper.offsetWidth - self._handle.offsetWidth;
                                        else if(self._vertical && posx > self._outerWrapper.offsetHeight - self._handle.offsetHeight) posx = self._outerWrapper.offsetHeight - self._handle.offsetHeight;

                                        if(self._tween) {
                                                self.tweenTo(posx);
                                        } else {
                                                self._posx = posx;
                                                fdSliderController.addEvent(document, 'mouseup', self._events.onmouseup);
                                                self.onTimer();
                                        }
                                }
                        }
                };

                this.onTimer = function() {
                        var xtmp = self._vertical ? self._handle.offsetTop : self._handle.offsetLeft;

                        if(self._posx < xtmp) {
                                xtmp -= self._incPx;
                                xtmp = Math.floor(xtmp);
                                if(xtmp < self._posx) xtmp = self._posx;
                        } else {
                                xtmp += self._incPx;
                                xtmp = Math.ceil(xtmp);
                                if(xtmp > self._posx) xtmp = self._posx;
                        }

                        self.updateInput(xtmp);
                        if(self._vertical) {
                                self._handle.style.top = xtmp + "px";
                        } else {
                                self._handle.style.left = xtmp + "px";
                        }

                        if(xtmp != self._posx) self._timer = setTimeout(self.onTimer, 200);
                        else self._kbEnabled = true;
                }

                this.calculateRange = function() {
                        var sW = self._vertical ? self._outerWrapper.offsetHeight : self._outerWrapper.offsetWidth;
                        var hW = self._vertical ? self._handle.offsetHeight : self._handle.offsetWidth;
                        this._incPx    = (sW - hW) / 10 < 1 ? 1 : (sW - hW) / 10;
                }

                this.locate = function(){
                        var curleft = 0;
                        var curtop  = 0;
                        var obj = self._outerWrapper;

                        // Try catch for IE
                        try {
                                while (obj.offsetParent) {
                                        curleft += obj.offsetLeft;
                                        curtop  += obj.offsetTop;
                                        obj      = obj.offsetParent;
                                };
                        } catch (e) {

                        }

                        self._x = curleft;
                        self._y = curtop;
                };

                this.tweenTo = function(x){
                        self._kbEnabled = false;
                        self._tweenX = parseInt(x);
                        self._tweenB = parseInt(self._vertical ? self._handle.style.top : self._handle.style.left);
                        self._tweenC = self._tweenX - self._tweenB;
                        self._tweenD = 20;
                        self._frame  = 0;
                        if (!self._timer) self._timer = setTimeout(self.tween,50);
                };

                this.tween = function(){
                        self._frame++;
                        var c = self._tweenC;
                        var d = 20;
                        var t = self._frame;
                        var b = self._tweenB;
                        var x = Math.ceil((t==d) ? b+c : c * (-Math.pow(2, -10 * t/d) + 1) + b);
                        if(self._vertical) self._handle.style.top = x + "px";
                        else self._handle.style.left = x + "px";
                        self.updateInput(x);
                        if(t!=d && !self._md) self._timer = setTimeout(self.tween,20);
                        else {
                                if(self._vertical) self._handle.style.top = self._tweenX + "px";
                                else self._handle.style.left = self._tweenX + "px";
                                clearTimeout(self._timer);
                                self._timer = null;
                                self._kbEnabled = true;
                        };
                };

                this.updateInput = function(x) {
                        var max = self._vertical ? self._outerWrapper.offsetHeight - self._handle.offsetHeight : self._outerWrapper.offsetWidth - self._handle.offsetWidth;
                        var inc = max / self._range;

                        var val = self._vertical ? -Math.floor((x / inc) + -Math.abs(self._min)) : Math.floor((x / inc) + Math.abs(self._min));

                        if(val < self._min) val = self._min;
                        else if(val > self._max) val = self._max;
                        
                        self._inp.value = val;
                        if(self._callback && self._callback in window) window[self._callback]();
                };

                this.calcFromInput = function() {
                        var value = parseInt(self._inp.value);
                        if(isNaN(value)) value = self._min;
                        
                        if(value < self._min) value = self._min;
                        else if(value > self._max) value = self._max;

                        self._inp.value = value;

                        var max = self._vertical ? self._outerWrapper.offsetHeight - self._handle.offsetHeight : self._outerWrapper.offsetWidth - self._handle.offsetWidth;
                        var inc = max / self._range;
                        var tot = value - self._min;

                        return Math.ceil(tot * inc);
                };

                this.build = function() {
                        if(self._hideInput) self._inp.style.display = "none";
                        else fdSliderController.addEvent(self._inp,'change',self._events.onchange);

                        self._outerWrapper               = document.createElement('div');
                        self._outerWrapper.className     = "fd-slider" + (self._vertical ? "-vertical " : " ") + self._classname;
                        self._outerWrapper.id            = "fd-slider-" + inp.name;

                        self._wrapper                   = document.createElement('div');
                        self._wrapper.className         = "fd-slider-inner";

                        self._bar                       = document.createElement('div');
                        self._bar.className             = "fd-slider-bar";

                        self._handle                    = document.createElement('div');
                        self._handle.className          = "fd-slider-handle";

                        self._wrapper.appendChild(self._bar);
                        self._wrapper.appendChild(self._handle);
                        self._outerWrapper.appendChild(self._wrapper);

                        self._inp.parentNode.insertBefore(self._outerWrapper, self._inp);

                        // Ghecko
                        self._wrapper.setAttribute("autoComplete", "off");
                        self._wrapper.setAttribute("tabIndex", "1");

                        self._wrapper.onblur            = self._events.onblur;
                        self._wrapper.onfocus           = self._events.onfocus;

                        /*@cc_on@*/
                        /*@if(@_win32)
                        self._bar.onblur  = self._handle.onblur  = self._events.onblur;
                        self._bar.onfocus = self._handle.onfocus = self._events.onfocus;
                        self._handle.unselectable       = "on";
                        self._bar.unselectable          = "on";
                        self._wrapper.unselectable      = "on";
                        self._outerWrapper.unselectable = "on";
                        /*@end@*/

                        fdSliderController.addEvent(self._handle,  "mouseover", self._events.onmouseover);
                        fdSliderController.addEvent(self._handle,  "mouseout",  self._events.onmouseout);
                        fdSliderController.addEvent(self._wrapper, "mousedown", self._events.onmousedown);
                        fdSliderController.addEvent(self._wrapper, "keypress",  self._events.onkeypress);
                        fdSliderController.addEvent(self._wrapper, "keydown",   self._events.onkeydown);
                        self.recalculate();
                };

                this.resetPosition = function() {
                        var max = self._vertical ? self._outerWrapper.offsetHeight - self._handle.offsetHeight : self._outerWrapper.offsetWidth - self._handle.offsetWidth;
                        var inc = max / self._range;
                        if(self._vertical) {
                                self._handle.style.top = Math.ceil(self._inp.value * inc) + "px";
                        } else {
                                self._handle.style.left = Math.ceil(self._inp.value * inc) + "px";
                        }
                };

                this.recalculate = function() {
                        self.locate();
                        // Internet Explorer requires the try catch
                        try {
                                var sW = self._outerWrapper.offsetWidth;
                                var sH = self._outerWrapper.offsetHeight;

                                var hW = self._handle.offsetWidth;
                                var hH = self._handle.offsetHeight;
                                var bH = self._bar.offsetHeight;
                                var bW = self._bar.offsetWidth;

                                if(self._vertical) {
                                        self._bar.style.height = Math.round(sH - hH) + "px";
                                        self._bar.style.left   = Math.floor((sW - bW) / 2) + "px";
                                        self._bar.style.top    = Math.round(hH / 2) + "px";
                                } else {
                                        self._bar.style.width  = Math.round(sW - hW) + "px";
                                        self._bar.style.left   = Math.round(hW / 2) + "px";
                                        self._bar.style.top    = Math.floor((sH - bH) / 2) + "px";
                                }

                                self.calculateRange();

                                var pos = self.calcFromInput();
                                if(self._vertical) {
                                        self._handle.style.top = pos + "px";
                                } else {
                                        self._handle.style.left = pos + "px";
                                }
                        } catch (e) {

                        }
                };
                var self = this;
                fdSliderController.addEvent(window, 'resize', self.recalculate);
                self.build();
        };

        fdSliderController = {
                sliders: {},
                tab: 1,
                uniqueid: 1,

                addEvent: function(obj, type, fn) {
                        if( obj.attachEvent ) {
                                obj["e"+type+fn] = fn;
                                obj[type+fn] = function(){obj["e"+type+fn]( window.event );}
                                obj.attachEvent( "on"+type, obj[type+fn] );
                        } else
                                obj.addEventListener( type, fn, true );
                },

                removeEvent: function(obj, type, fn) {
                        if( obj.detachEvent ) {
                                obj.detachEvent( "on"+type, obj[type+fn] );
                                obj[type+fn] = null;
                        } else
                                obj.removeEventListener( type, fn, true );
                },

                _construct: function( e ) {
                        var regExp_1 = /fd_range_([-]{0,1}[0-9]+){1}_([-]{0,1}[0-9]+){1}/ig;
                        var regExp_2 = /fd_callback_([a-zA-Z0-9_]+)/ig;
                        var regExp_3 = /fd_classname_([a-zA-Z0-9_\-]+)/ig;

                        var inputs    = document.getElementsByTagName("input");
                        var classname = "";
                        var func      = "";
                        var hide      = false;
                        var tween     = true;
                        var vertical  = false;

                        var callback, cName, startIndex, range;

                        for(var i = 0, inp; inp = inputs[i]; i++) {
                                if(inp.type == "text" && inp.className && inp.className.search(regExp_1) != -1) {
                                        // Has the slider already been created?
                                        if(document.getElementById("fd-slider-"+inp.name)) continue;

                                        // range
                                        range = inp.className.match(regExp_1)[0].replace(/fd_range_/, '').split('_');

                                        // callback function
                                        callback = "";
                                        if(inp.className.search(regExp_2) != -1) {
                                                callback = inp.className.match(regExp_2)[0].replace(/fd_callback_/, '');
                                        }

                                        // extra classname to assign to the wrapper div
                                        classname = "";
                                        if(inp.className.search(regExp_3) != -1) {
                                                classname = inp.className.match(regExp_3)[0].replace(/fd_classname_/, '');
                                        }

                                        // hide associated input
                                        hide  = inp.className.search(/fd_hide_input/ig) != -1;

                                        // use the tween animation
                                        tween = inp.className.search(/fd_tween/ig) != -1;

                                        // vertical
                                        vertical = inp.className.search(/fd_vertical/ig) != -1;

                                        fdSliderController.sliders[inp.name] = new fdSlider(inp, range, callback, classname, hide, tween, vertical);
                                }
                        }

                },

                _deconstruct: function( e ) {
                        /* TODO */
                }
        }
})();

// Slider specific
fdSliderController.addEvent(window, 'load',   fdSliderController._construct);
fdSliderController.addEvent(window, 'unload', fdSliderController._deconstruct);

