Utility styles are a collection of useful classes designed to reduce the need to create a stylesheet for basic styling needs, such as spacing and positioning.

### Typography

```css
.t-ww { word-wrap: break-word; }
.t-nw { white-space: nowrap; }
```

### Positioning

```css
.pos-r { position: relative !important; }
.pos-a { position: absolute !important; }
.pos-f { position: fixed !important; }
```

### Width

```css
.w-auto { width:auto !important }

.w-25   { width: 25% !important; }
.w-50   { width: 50% !important; }
.w-75   { width: 75% !important; }
.w-100 { width: 100% !important; }

.w-60   { width: 60px !important; }
.w-120  { width: 120px !important; }
.w-130  { width: 130px !important; }
.w-140  { width: 140px !important; }
.w-150  { width: 150px !important; }
.w-200  { width: 200px !important; }
.w-300  { width: 300px !important; }
.w-350  { width: 350px !important; }

.mw-950 { max-width: 950px !important; }
```

### Margin

Assign `margin` to an element with these shorthand classes. The `@spacer` value is set to 20px by default.

```css
.m-0    { margin: 0 !important; }
.m-1    { margin: (@spacer * .25) !important; }
.m-2    { margin: (@spacer * .5) !important; }
.m-3    { margin: @spacer !important; }
.m-4    { margin: (@spacer * 1.5) !important; }
.m-5    { margin: (@spacer * 3) !important; }
.m-auto { margin: auto !important; }

.mx-0    { margin-left: 0 !important; margin-right: 0 !important; }
.mx-1    { margin-left: (@spacer-x * .25) !important; margin-right: (@spacer-x * .25) !important; }
.mx-2    { margin-left: (@spacer-x * .5) !important; margin-right: (@spacer-x * .5) !important; }
.mx-3    { margin-left: @spacer-x !important; margin-right: @spacer-x !important; }
.mx-4    { margin-left: (@spacer-x * 1.5) !important; margin-right:(@spacer-x *  1.5) !important; }
.mx-5    { margin-left: (@spacer-x * 3) !important; margin-right: (@spacer-x * 3) !important; }
.mx-auto { margin-left: auto !important; margin-right: auto !important; }

.my-0    { margin-bottom: 0 !important; margin-top: 0 !important; }
.my-1    { margin-bottom: (@spacer-y * .25) !important; margin-top: (@spacer-y * .25) !important; }
.my-2    { margin-bottom: (@spacer-y * .5) !important; margin-top: (@spacer-y * .5) !important; }
.my-3    { margin-bottom: @spacer-y !important; margin-top: @spacer-y !important; }
.my-4    { margin-bottom: (@spacer-y * 1.5) !important; margin-top:(@spacer-y *  1.5) !important; }
.my-5    { margin-bottom: (@spacer-y * 3) !important; margin-top: (@spacer-y * 3) !important; }
.my-auto { margin-bottom: auto !important; margin-top: auto !important; }

.mt-0    { margin-top: 0 !important; }
.mt-1    { margin-top: (@spacer-y * .25) !important; }
.mt-2    { margin-top: (@spacer-y * .5) !important; }
.mt-3    { margin-top: @spacer-y!important; }
.mt-4    { margin-top: (@spacer-y * 1.5) !important; }
.mt-5    { margin-top: (@spacer-y * 3) !important; }
.mt-auto { margin-top: auto !important; }

.me-0    { margin-right: 0 !important; }
.me-1    { margin-right: (@spacer-x * .25) !important; }
.me-2    { margin-right: (@spacer-x * .5) !important; }
.me-3    { margin-right: @spacer-x !important; }
.me-4    { margin-right: (@spacer-x * 1.5) !important; }
.me-5    { margin-right: (@spacer-x * 3) !important; }
.me-auto { margin-right: auto !important; }

.mb-0    { margin-bottom: 0 !important; }
.mb-1    { margin-bottom: (@spacer-y * .25) !important; }
.mb-2    { margin-bottom: (@spacer-y * .5) !important; }
.mb-3    { margin-bottom: @spacer-y !important; }
.mb-4    { margin-bottom: (@spacer-y * 1.5) !important; }
.mb-5    { margin-bottom: (@spacer-y * 3) !important; }
.mb-auto { margin-bottom: auto !important; }

.ms-0    { margin-left: 0 !important; }
.ms-1    { margin-left: (@spacer-x * .25) !important; }
.ms-2    { margin-left: (@spacer-x * .5) !important; }
.ms-3    { margin-left: @spacer-x !important; }
.ms-4    { margin-left: (@spacer-x * 1.5) !important; }
.ms-5    { margin-left: (@spacer-x * 3) !important; }
.ms-auto { margin-left: auto !important; }
```

### Padding

Assign `padding` to an element with these shorthand classes. The `@spacer` value is set to 20px by default.

```css
.p-0 { padding: 0 !important; }
.p-1 { padding: (@spacer * .25) !important; }
.p-2 { padding: (@spacer * .5) !important; }
.p-3 { padding: @spacer !important; }
.p-4 { padding: (@spacer * 1.5) !important; }
.p-5 { padding: (@spacer * 3) !important; }

.px-0 { padding-left: 0 !important; padding-right: 0 !important; }
.px-1 { padding-left: (@spacer-x * .25) !important; padding-right: (@spacer-x * .25) !important; }
.px-2 { padding-left: (@spacer-x * .5) !important; padding-right: (@spacer-x * .5) !important; }
.px-3 { padding-left: @spacer-x !important; padding-right: @spacer-x !important; }
.px-4 { padding-left: (@spacer-x * 1.5) !important; padding-right:(@spacer-x *  1.5) !important; }
.px-5 { padding-left: (@spacer-x * 3) !important; padding-right: (@spacer-x * 3) !important; }

.py-0 { padding-bottom: 0 !important; padding-top: 0 !important; }
.py-1 { padding-bottom: (@spacer-y * .25) !important; padding-top: (@spacer-y * .25) !important; }
.py-2 { padding-bottom: (@spacer-y * .5) !important; padding-top: (@spacer-y * .5) !important; }
.py-3 { padding-bottom: @spacer-y !important; padding-top: @spacer-y !important; }
.py-4 { padding-bottom: (@spacer-y * 1.5) !important; padding-top:(@spacer-y *  1.5) !important; }
.py-5 { padding-bottom: (@spacer-y * 3) !important; padding-top: (@spacer-y * 3) !important; }

.pt-0 { padding-top: 0 !important; }
.pt-1 { padding-top: (@spacer-y * .25) !important; }
.pt-2 { padding-top: (@spacer-y * .5) !important; }
.pt-3 { padding-top: @spacer-y!important; }
.pt-4 { padding-top: (@spacer-y * 1.5) !important; }
.pt-5 { padding-top: (@spacer-y * 3) !important; }

.pe-0 { padding-right: 0 !important; }
.pe-1 { padding-right: (@spacer-x * .25) !important; }
.pe-2 { padding-right: (@spacer-x * .5) !important; }
.pe-3 { padding-right: @spacer-x !important; }
.pe-4 { padding-right: (@spacer-x * 1.5) !important; }
.pe-5 { padding-right: (@spacer-x * 3) !important; }

.pb-0 { padding-bottom: 0 !important; }
.pb-1 { padding-bottom: (@spacer-y * .25) !important; }
.pb-2 { padding-bottom: (@spacer-y * .5) !important; }
.pb-3 { padding-bottom: @spacer-y !important; }
.pb-4 { padding-bottom: (@spacer-y * 1.5) !important; }
.pb-5 { padding-bottom: (@spacer-y * 3) !important; }

.ps-0 { padding-left: 0 !important; }
.ps-1 { padding-left: (@spacer-x * .25) !important; }
.ps-2 { padding-left: (@spacer-x * .5) !important; }
.ps-3 { padding-left: @spacer-x !important; }
.ps-4 { padding-left: (@spacer-x * 1.5) !important; }
.ps-5 { padding-left: (@spacer-x * 3) !important; }
```
