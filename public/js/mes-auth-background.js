/* =============================================================================
   MES auth background - video nền cho trang Login / Register / Forgot password
   (Views/Shared/_AuthLayout.cshtml).

   Hai thẻ <video> chồng nhau: video đang phát sắp hết (còn FADE_DURATION_SECOND) thì
   video kế tiếp bắt đầu phát ở thẻ còn lại, rồi đổi opacity -> hiệu ứng cross-fade.
   Chỉ 1 video: phát lặp (loop), không fade.

   Không phát video khi:
     - người dùng bật "Reduce motion" trong hệ điều hành (prefers-reduced-motion)
     - trình duyệt chặn autoplay -> giữ nền tĩnh, trang vẫn dùng bình thường
   ============================================================================= */
(function (window, document) {
    "use strict";

    var FADE_DURATION_SECOND = 1.5;

    /* Dữ liệu cấu hình sai không được làm hỏng trang đăng nhập: lỗi -> coi như không có video. */
    function parseVideoUrls(videoUrlsJson) {
        try {
            var parsedValue = JSON.parse(videoUrlsJson || "[]");
            return Array.isArray(parsedValue) ? parsedValue : [];
        } catch (error) {
            return [];
        }
    }

    var backgroundElement = document.getElementById("mesAuthBackground");
    if (!backgroundElement) {
        return;
    }

    var prefersReducedMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    var videoUrls = parseVideoUrls(backgroundElement.getAttribute("data-video-urls"));
    if (prefersReducedMotion || videoUrls.length === 0) {
        backgroundElement.classList.add("mes-auth-background-static");
        return;
    }

    var videoElements = backgroundElement.querySelectorAll("video");
    var activeVideo = videoElements[0];
    var standbyVideo = videoElements[1];
    var activeVideoIndex = 0;
    var isSwitching = false;

    backgroundElement.style.setProperty("--mes-auth-fade-duration", FADE_DURATION_SECOND + "s");

    function playVideo(videoElement, videoUrl) {
        if (videoElement.getAttribute("src") !== videoUrl) {
            videoElement.setAttribute("src", videoUrl);
        }
        videoElement.currentTime = 0;
        return videoElement.play();
    }

    function switchToNextVideo() {
        if (isSwitching) {
            return;
        }
        isSwitching = true;

        var nextVideoIndex = (activeVideoIndex + 1) % videoUrls.length;
        playVideo(standbyVideo, videoUrls[nextVideoIndex]).then(function () {
            standbyVideo.classList.add("mes-visible");
            activeVideo.classList.remove("mes-visible");

            var previousVideo = activeVideo;
            activeVideo = standbyVideo;
            standbyVideo = previousVideo;
            activeVideoIndex = nextVideoIndex;

            // Dừng video cũ sau khi đã mờ hẳn để tiết kiệm CPU / GPU.
            window.setTimeout(function () {
                previousVideo.pause();
                isSwitching = false;
            }, FADE_DURATION_SECOND * 1000);
        }).catch(function () {
            isSwitching = false;
        });
    }

    function handleTimeUpdate(event) {
        var videoElement = event.target;
        if (videoElement !== activeVideo || !videoElement.duration) {
            return;
        }

        if (videoElement.duration - videoElement.currentTime <= FADE_DURATION_SECOND) {
            switchToNextVideo();
        }
    }

    function handleEnded(event) {
        // Dự phòng khi timeupdate không kịp bắn trước lúc hết video (video rất ngắn).
        if (event.target === activeVideo) {
            switchToNextVideo();
        }
    }

    videoElements.forEach(function (videoElement) {
        videoElement.addEventListener("timeupdate", handleTimeUpdate);
        videoElement.addEventListener("ended", handleEnded);
    });

    if (videoUrls.length === 1) {
        activeVideo.loop = true;
    } else {
        // Nạp trước video thứ hai để lần chuyển đầu tiên không bị giật.
        standbyVideo.setAttribute("src", videoUrls[1]);
    }

    playVideo(activeVideo, videoUrls[0])
        .then(function () { activeVideo.classList.add("mes-visible"); })
        .catch(function () { backgroundElement.classList.add("mes-auth-background-static"); });
})(window, document);
