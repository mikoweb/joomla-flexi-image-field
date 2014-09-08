// ==UserScript==
// @name        FLEXIContent - Autosave articles
// @namespace   flexicontent
// @include 	/^https?://.*/administrator/index.php\?option=com_flexicontent&view=items.*$/
// @version     1
// @grant       none
// ==/UserScript==

/**
 * Greasemonkey script to atosaving FLEXIContent articles
 * This file is part of the Joomla FlexiContent Image Field
 *
 * @package Joomla FlexiContent Image Field
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @copyright 2014 Rafał Mikołajun
 * @license MIT
 */
(function () {
    "use strict";

    window.addEventListener('load', function() {
        var limit = 10,
            start = 0,
            urlTest = /^https?:\/\/.*\/administrator\/index.php\?option=com_flexicontent&view=items&limit=\d+.*$/,
            startTest = /^https?:\/\/.*\/administrator\/index.php\?option=com_flexicontent&view=items&limit=\d+&limitstart=(\d+).*$/,
            changeStartRegex = /^(https?:\/\/.*\/administrator\/index.php\?option=com_flexicontent&view=items&limit=\d+&limitstart=)\d+(.*)$/,
            match = window.location.href.match(startTest);

        if (!urlTest.test(window.location)) {
            window.location = window.location + '&limit=' + limit + '&limitstart=' + start;
        }
        start = parseInt(match[1], 10);

        (function () {
            var editLinks = document.querySelectorAll("table.adminlist td.col_title .editlinktip a")
                , container = document.createElement("div")
                , i = 0, complete = 0;

            function generateIframe(link)
            {
                var iframe = document.createElement("iframe");

                iframe.onload = function () {
                    var iFrameDocument;

                    iframe.onload = function () {
                        complete++;
                        container.querySelector('.itemsComplete').textContent = complete.toString();
                        container.querySelector('.progress .bar').style.width = (complete/editLinks.length*100).toString() + '%';
                        iframe.setAttribute("src", "");
                        iframe.parentNode.removeChild(iframe);

                        if (complete === editLinks.length) {
                            window.location = window.location.href.replace(changeStartRegex, '$1' + (start + limit).toString() + '$2');
                        }
                    };

                    if (iframe.contentDocument) {
                        iFrameDocument = iframe.contentDocument;
                    } else if (iframe.contentWindow) {
                        iFrameDocument = iframe.contentWindow.document;
                    }

                    iFrameDocument.querySelector("#toolbar-apply button").click();
                };

                iframe.setAttribute("src", link);
                iframe.style.width = 400+"px";
                iframe.style.height = 200+"px";
                iframe.style.position = "absolute";
                iframe.style.left = "40px";
                iframe.style.top = "40px";
                iframe.style.backgroundColor = "#ffffff";
                document.body.appendChild(iframe);
            }

            for (i = 0; i < editLinks.length; i++) {
                (function (linkNo) {
                    setTimeout(function () {
                        generateIframe(editLinks[linkNo].getAttribute("href"));
                    }, 50 * linkNo);
                }(i));
            }

            container.innerHTML = '<div class="hero-unit">'
                + '<h2 style="font-weight: normal">Progress: <b><span class="itemsComplete">0</span> / <span class="itemsNum">' + editLinks.length + '</span></b></h2>'
                + '<div class="progress">'
                + '<div class="bar" style="width: 0%;"></div>'
                + '</div>'
                + '</div>';

            container.setAttribute("id", "greasemonkeyAutosaveStatus");
            container.style.width = 400+"px";
            container.style.height = 400+"px";
            container.style.position = "absolute";
            container.style.right = "40px";
            container.style.top = "40px";
            document.body.appendChild(container);
        }());
    }, false);
}());
