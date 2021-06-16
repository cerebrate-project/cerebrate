<?php
namespace App\View\Helper;

use Cake\View\Helper;


/**
 * Allow creating element with their CSS and JS scoped to only themselves.
 * Usage:
 *      echo $this->ScopedElement->element('element-path', $elementData, $elementOptions);
 * 
 * For a scoped element to work properly, the following must be done:
 *  - The <script> tag must have an attribute `element-scoped` with the value of the variable used to instanciate the AppElement class. This allow the helper to recognise which variable is holding the app logic
 *  - The <style> tag must have the `element-scoped` property to correctly scope the classes to only this element
 * If any of these two tags do not have the `element-scoped` attribute, it will be treated as global definition. Doing such is not advised as these definition should be put in the misp.js and main.css file instead.
 * 
 * There is one final caveat when declaring scoped classes. Please refer to the documentation of the `createScopedCSS` function for more information.
 * 
 * Example of a scoped element:
 * ```
 * <button class="btn cool-button" onclick="appName.sayHi()">Click me</button>
 * 
 * <script element-scoped="appName">
 *     const appName = new AppElement({
 *         data: {
 *             color: 'blue'
 *         },
 *         methods: {
 *             sayHi: function() {
 *                 console.log('Hi from ' + this.color)
 *             }
 *         },
 *         mounted() {
 *             console.log("AppElement is mounted!")
 *         }
 *     })
 * 
 *     appName.$el // the HTML tag in which the app is running for can be accessed via the `el` and `$el` property
 *     appName.$el.data('appElement') === appName // the app can be retrieved from the HTML tag by requesting the `appElement` data
 * </script>
 * 
 * <style element-scoped>
 *     .cool-button {
 *         height: 2em;
 *     }
 * </style>
 * ```
 */
class ScopedElementHelper extends Helper {

    public function element($elementPath, $elementData=[], $elementOptions=[]): String
    {
        if (!isset($this->seedDepth)) {
            $this->seedDepth = [];
            $this->processedSeeds = [];
        }
        $seed = rand();
        $this->seedDepth[] = $seed;
        $this->processedSeeds[] = $seed;
        $elementHtml = $this->_View->element($elementPath, $elementData, $elementOptions);
        $scopedHtml = $this->createScoped($elementHtml);
        array_pop($this->seedDepth);
        return $scopedHtml;
    }

    private function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
        return (substr($haystack, -$length) === $needle);
    }

    private function preppendScopedId($css)
    {
        $prependSelector = array_map(function($seed) {
            return sprintf('[data-scoped="%s"]', $seed);
        }, $this->seedDepth);
        $prependSelector = implode(' ', $prependSelector);
        $cssLines = explode(PHP_EOL, $css);
        foreach ($cssLines as $i => $line) {
            if (strlen($line) > 0) {
                if ($this->endsWith($line, "{") || $this->endsWith($line, ",")) {
                    $cssLines[$i] = sprintf("%s %s", $prependSelector, $line);
                }
            }
        }
        $cssScopedLines = implode(PHP_EOL, $cssLines);
        return sprintf("<style>%s%s%s</style>", PHP_EOL, $cssScopedLines, PHP_EOL);
    }

    private function createScoped($html): String
    {
        $scopedCSS = $this->createScopedCSS($html)['bundle'];
        $scopedHtml = $this->createScopedJS($scopedCSS)['bundle'];
        return $scopedHtml;
    }


    /**
     * Replace a declared CSS scoped style and prepend a random CSS data filter to any CSS selector discovered.
     * Usage: Add the following style tag `<style widget-scoped>` to use the scoped feature. Nearly every selector path will have their rule modified to adhere to the scope
     * Restrictions:
     *      - Applying class to the root document (i.e. `body`) will not work
     *      - Selector rules must end with either `{` or `,`, their content MUST be put in a new line:
     *          [bad]
     *              element { ... }
     *          [good]
     *              element {
     *                  ...
     *              }
     *      - Selectors with the `and` (`,`) rule MUST be split in multiple lines:
     *          [bad]
     *              element,element {
     *                  ...
     *              }
     *          [good]
     *              element,
     *              element {
     *                  ...
     *              }
     * @param string $param1 HTML potentially containing scoped CSS
     * @return array Return an array composed of 3 keys (html, css and seed)
     *      - bundle:       Include both scoped HTML and scoped CSS or the original html if the scoped feature is not requested
     *      - html:         Untouched HTML including nested in a scoped DIV or original html if the scoped feature is not requested
     *      - css:          CSS with an additional filter rule prepended to every selectors or the empty string if the scoped feature is not requested
     *      - seed:         The random generated number
     *      - originalHtml: Untouched HTML
     */
    public function createScopedCSS($html): array
    {
        $css = "";
        $seed = end($this->seedDepth);
        $originalHtml = $html;
        $bundle = $originalHtml;
        $scopedHtml = $html;
        $scopedCSS = "";
        $htmlStyleTag = "<style element-scoped>";
        $styleClosingTag = "</style>";
        $styleTagIndex = strpos($html, $htmlStyleTag);
        $closingStyleTagIndex = strpos($html, $styleClosingTag, $styleTagIndex) + strlen($styleClosingTag);
        if ($styleTagIndex !== false && $closingStyleTagIndex !== false && $closingStyleTagIndex > $styleTagIndex) { // enforced scoped css
            $css = substr($html, $styleTagIndex, $closingStyleTagIndex - $styleTagIndex);
            $html = str_replace($css, "", $html); // remove CSS part
            $css = str_replace($htmlStyleTag, "", $css); // remove the style node
            $css = str_replace($styleClosingTag, "", $css); // remove closing style node
            $scopedCSS = $this->preppendScopedId($css);
            $scopedHtml = sprintf("<section style=\"display: contents;\" %s>%s%s</section>",
                sprintf("data-scoped=\"%s\" ", $seed),
                $html,
                $scopedCSS
            );
            $bundle = $scopedHtml;
        }
        return array(
            "bundle" => $bundle,
            "html" => $scopedHtml,
            "css" => $scopedCSS,
            "seed" => $seed,
            "originalHtml" => $originalHtml,
        );
    }

    private function varNameHasSeed($varname): bool
    {
        $pieces = explode('_', $varname);
        foreach ($pieces as $piece) {
            if (in_array($piece, $this->processedSeeds)) {
                return true;
            }
        }
        return false;
    }

    private function findNonProcessedScriptOpeningTag($html)
    {
        $offset = 0;
        $i = 0;
        while ($i<5) {
            $fullOpeningTagObj = $this->findScriptOpeningTag($html, $offset, true);
            $offset = $fullOpeningTagObj['openingTagClosingIndex'];
            if ($fullOpeningTagObj === false) { // no more tag to process
                return false;
            }
            $varName = $this->getScriptVarname($fullOpeningTagObj['fullOpeningTag']);
            if (!$this->varNameHasSeed($varName)) { // found unprocessed tag
                return $fullOpeningTagObj['fullOpeningTag'];
            }
            $i++;
        }
    }

    private function findScriptOpeningTag($html, $offset=0, $returnIndexes=false)
    {
        $openingTag = "<script element-scoped=\"";
        $closingTag = "</script>";
        $openingTagIndex = strpos($html, $openingTag, $offset);
        if ($openingTagIndex === false) {
            return false;
        }
        $openingTagClosingIndex = strpos($html, '>', $openingTagIndex) + 1;
        $fullOpeningTag = substr($html, $openingTagIndex, $openingTagClosingIndex - $openingTagIndex);
        if (!$returnIndexes) {
            return $fullOpeningTag;
        } else {
            return [
                'fullOpeningTag' => $fullOpeningTag,
                'openingTagIndex' => $openingTagIndex,
                'openingTagClosingIndex' => $openingTagClosingIndex,
            ];
        }
    }

    private function getScriptVarname($fullOpeningTag)
    {
        $openingTagReg = '/<script element-scoped="(?<varName>\w{3,})">/';
        preg_match($openingTagReg, $fullOpeningTag, $matches);
        if (!empty($matches['varName'])) {
            return $matches['varName'];
        }
        return false;
    }

    private function replaceAllNonProcessedVarNames($varName, $html)
    {
        $seed = end($this->seedDepth);
        $newVarName = sprintf('%s_%s', $varName, $seed);
        $allPossibleVarNames = array_map(function($seed) use ($varName) {
            return sprintf('%s_%s', $varName, $seed);
        }, $this->processedSeeds);
        $allVarNameReg = "/{$varName}[\w]*/";
        $scopedHtml = preg_replace_callback($allVarNameReg, function ($matches) use ($newVarName, $allPossibleVarNames) { // replace all occurences by new the varname if they haven't been processed yet
            if (in_array($matches[0], $allPossibleVarNames)) {
                return $matches[0];
            }
            return $newVarName;
        }, $html);
        return $scopedHtml;
    }

    /**
     * createScopedJS
     * 
     * Replaces all occurences of the application variable name in the provided HTML.
     * This application name comes from the defnintion in the script:
     * <script element-scoped="appName">
     *
     * @param  String $html
     * @return array
     */
    public function createScopedJS($html): array
    {
        $seed = end($this->seedDepth);
        $originalHtml = $html;
        $bundle = $originalHtml;
        $scopedHtml = $html;
        $fullOpeningTag = $this->findNonProcessedScriptOpeningTag($html);
        if ($fullOpeningTag !== false) {
            $varName = $this->getScriptVarname($fullOpeningTag);
            if (!empty($varName)) {
                $scopedHtml = $this->replaceAllNonProcessedVarNames($varName, $html);
                $bundle = $scopedHtml;
            }
        }
        return array(
            "bundle" => $bundle,
            "html" => $scopedHtml,
            "seed" => $seed,
            "originalHtml" => $originalHtml,
        );
    }

}
