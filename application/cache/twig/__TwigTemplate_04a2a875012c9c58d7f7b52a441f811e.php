<?php

/* index.html */
class __TwigTemplate_04a2a875012c9c58d7f7b52a441f811e extends Twig_Template
{
    public function display(array $context)
    {
        $this->checkSecurity();
        // line 1
        echo "<!doctype html>
<html>
<head>
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
    <title>Realty Assistant</title>

\t<link rel=\"Shortcut Icon\" type=\"image/x-icon\" href=\"/favicon.ico\" /> 
    <link type=\"text/css\" href=\"/media/css/ra.css\" rel=\"Stylesheet\" />\t

\t<script type=\"text/javascript\" src=\"/media/js/jquery-1.8.2.min.js\"></script>
    <script type=\"text/javascript\" src=\"/media/js/ui.js\"></script>
    <!--<script type=\"text/javascript\" src=\"http://maps.google.com/maps/api/js?sensor=false\"></script> -->

</head>
<body>
<div class=\"mask\" id=\"mask\"></div>

<div class=\"filter-box\">

\t<div class=\"f-item-outer\">
\t\t<div class=\"f-item-inner\"></div>
\t\t
\t\t<div class=\"title\">Some Button</div>
\t</div>

</div>

<div style=\"margin-top:150px; text-align:center;\">SOME test text and images or other content <img src=\"media/img/car.jpg\"/></div>

</body>
</html>";
    }

    protected function checkSecurity() {
        $this->env->getExtension('sandbox')->checkSecurity(
            array(),
            array()
        );
    }

}
