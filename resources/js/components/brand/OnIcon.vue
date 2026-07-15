<template>
  <span class="on-mark" role="img" aria-label="On">
    <span class="on-toggle" aria-hidden="true"><span class="on-knob"></span></span>
    <span class="on-word" aria-hidden="true">N</span>
  </span>
</template>

<style scoped>
.on-mark {
  display: inline-flex;
  height: .72em;
  align-items: center;
  gap: .035em;
  color: inherit;
  line-height: 1;
  vertical-align: -.08em;
  flex-shrink: 0;
}
.on-toggle {
  display: inline-flex;
  width: .78em;
  height: .62em;
  align-items: center;
  justify-content: flex-end;
  padding: .07em;
  border: .025em solid rgba(255, 255, 255, .18);
  border-radius: .32em;
  background: linear-gradient(180deg, #45c65b 0%, #16953b 100%);
  box-shadow: inset 0 .04em .08em rgba(255, 255, 255, .22), 0 .07em .16em rgba(0, 0, 0, .24);
}
.on-knob {
  width: .46em;
  height: .46em;
  flex: 0 0 .46em;
  border-radius: 50%;
  background: #fff;
  box-shadow: 0 .03em .08em rgba(0, 0, 0, .28);
}
.on-word {
  color: inherit;
  font-size: .62em;
  font-weight: 950;
  font-style: italic;
  letter-spacing: -.04em;
  transform: translateY(-.01em);
}
</style>
