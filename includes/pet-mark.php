<?php
declare(strict_types=1);

/**
 * The Something About Pets cat + dog line mark as an inline, animatable SVG.
 * Redrawn as strokes from the logo so it can "draw itself" (see assets/css/pets-anim.css).
 *
 * @param string $class  extra classes, e.g. 'pet-mark--header'
 * @param bool   $draw   play the line-drawing intro animation
 */
function pet_mark(string $class = '', bool $draw = true, string $label = 'Something About Pets'): string
{
    $cls = trim('pet-mark ' . ($draw ? 'pet-mark--draw ' : '') . $class);
    return <<<SVG
<svg class="{$cls}" viewBox="585 85 820 380" role="img" aria-label="{$label}" xmlns="http://www.w3.org/2000/svg">
  <g class="pm-lines" fill="none" stroke="currentColor" stroke-width="17" stroke-linecap="round" stroke-linejoin="round">
    <!-- cat: body, ears and the line that becomes the dog's ear -->
    <path class="pm-stroke pm-s1" pathLength="1" d="M645 402 L668 255 C676 208 686 172 697 156 C709 139 733 133 751 143 C763 151 768 168 772 181 L852 168 C861 148 876 117 893 103 C935 134 966 165 990 193"/>
    <g class="pm-dog-ear">
      <path class="pm-stroke pm-s2" pathLength="1" d="M990 193 C960 232 944 300 947 360 C949 401 975 413 1001 405 C1036 395 1051 360 1041 320 C1031 270 1010 225 990 193"/>
    </g>
    <!-- dog head -->
    <path class="pm-stroke pm-s3" pathLength="1" d="M990 193 L1036 183 C1061 136 1111 100 1171 98 C1241 98 1286 141 1301 215"/>
    <path class="pm-stroke pm-s4" pathLength="1" d="M1196 238 L1300 221"/>
    <path class="pm-stroke pm-s5" pathLength="1" d="M1114 293 C1131 312 1161 313 1183 297"/>
    <path class="pm-stroke pm-s6" pathLength="1" d="M1121 314 C1160 360 1214 398 1266 410"/>
    <path class="pm-stroke pm-s7" pathLength="1" d="M1303 298 C1306 332 1301 370 1291 396"/>
    <g class="pm-tongue">
      <path class="pm-stroke pm-s8" pathLength="1" d="M1266 406 C1262 442 1300 455 1319 433 C1331 419 1326 401 1318 395"/>
    </g>
    <!-- cat face -->
    <path class="pm-stroke pm-s9" pathLength="1" d="M782 247 C785 268 806 272 816 254 C826 272 849 268 852 244"/>
    <path class="pm-stroke pm-s10" pathLength="1" d="M603 290 L690 300"/>
    <path class="pm-stroke pm-s11" pathLength="1" d="M607 346 L697 345"/>
  </g>
  <g class="pm-fills" fill="currentColor">
    <path class="pm-nose" d="M1262 227 C1301 218 1351 215 1386 221 C1393 246 1371 276 1341 293 C1311 306 1286 296 1271 276 C1263 263 1258 241 1262 227Z"/>
    <g class="pm-dog-eyes"><ellipse cx="1135" cy="181" rx="16" ry="21"/><ellipse cx="1218" cy="183" rx="16" ry="21"/></g>
    <g class="pm-cat-eyes"><ellipse cx="723" cy="262" rx="8" ry="12"/><ellipse cx="916" cy="236" rx="8" ry="12"/></g>
    <circle cx="1236" cy="309" r="6"/><circle cx="1262" cy="326" r="6"/><circle cx="1229" cy="341" r="6"/>
  </g>
</svg>
SVG;
}
