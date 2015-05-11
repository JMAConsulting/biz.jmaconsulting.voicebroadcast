cj(document).ready(function(){
      cj("#jquery_jplayer_1").jPlayer({
        ready: function () {
          cj(this).jPlayer("setMedia", {
            title: "Voice Recording",
            m4a: "http://www.jplayer.org/audio/m4a/Miaow-07-Bubble.m4a"
          });
        },
        cssSelectorAncestor: "#jp_container_1",
        swfPath: ".",
        supplied: "m4a",
        useStateClassSkin: true,
        autoBlur: false,
        smoothPlayBar: true,
        keyEnabled: true,
        remainingDuration: true,
        toggleDuration: true
      });
    });
