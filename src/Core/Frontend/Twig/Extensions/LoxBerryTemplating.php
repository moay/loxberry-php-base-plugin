<?php

namespace LoxBerryPlugin\Core\Frontend\Twig\Extensions;

use LoxBerry\ConfigurationParser\SystemConfigurationParser;
use LoxBerry\System\Localization\LanguageDeterminator;
use LoxBerry\System\PathProvider;
use LoxBerry\System\Paths;
use LoxBerryPlugin\Core\Frontend\Twig\Utility\NavigationBarBuilder;
use LoxBerryPlugin\Core\Frontend\Twig\Utility\TranslatedSystemTemplateLoader;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class LoxBerryTemplating.
 */
class LoxBerryTemplating extends AbstractExtension
{
    /** @var PathProvider */
    private $pathProvider;

    /** @var string */
    private $templateDirectory;

    /** @var SystemConfigurationParser */
    private $systemConfigurationParser;

    /** @var LanguageDeterminator */
    private $languageDeterminator;

    /** @var NavigationBarBuilder */
    private $navigationBarBuilder;

    /** @var TranslatedSystemTemplateLoader */
    private $systemTemplateLoader;

    /**
     * LoxBerryTemplating constructor.
     *
     * @param PathProvider $pathProvider
     * @param SystemConfigurationParser $systemConfigurationParser
     * @param LanguageDeterminator $languageDeterminator
     * @param NavigationBarBuilder $navigationBarBuilder
     * @param TranslatedSystemTemplateLoader $systemTemplateLoader
     */
    public function __construct(
        PathProvider $pathProvider,
        SystemConfigurationParser $systemConfigurationParser,
        LanguageDeterminator $languageDeterminator,
        NavigationBarBuilder $navigationBarBuilder,
        TranslatedSystemTemplateLoader $systemTemplateLoader
    ) {
        $this->pathProvider = $pathProvider;
        $this->templateDirectory = rtrim($this->pathProvider->getPath(Paths::PATH_SYSTEM_TEMPLATE), '/');
        $this->systemConfigurationParser = $systemConfigurationParser;
        $this->languageDeterminator = $languageDeterminator;
        $this->navigationBarBuilder = $navigationBarBuilder;
        $this->systemTemplateLoader = $systemTemplateLoader;
    }

    /**
     * @return array|TwigFunction[]
     */
    public function getFunctions(): array
    {
        return array(
            new TwigFunction('loxBerryHtmlHead',
                [$this, 'htmlHead'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction('loxBerryHtmlFoot',
                [$this, 'htmlFoot'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction('loxBerryPageStart',
                [$this, 'pageStart'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction('loxBerryPageEnd',
                [$this, 'pageEnd'],
                ['is_safe' => ['html']]
            ),
        );
    }

    /**
     * @param string|null $pageTitle
     * @param string|null $htmlHead
     *
     * @return string
     */
    public function htmlHead(?string $pageTitle = null, ?string $htmlHead = ''): string
    {
        $templateFile = $this->templateDirectory . '/head.html';
        $template = $this->systemTemplateLoader->loadTranslatedFile($templateFile);

        return $this->readTemplate($template, [
            'TEMPLATETITLE' => $this->getPrintedPageTitle($pageTitle),
            'LANG' => $this->languageDeterminator->getLanguage(),
            'HTMLHEAD' => $htmlHead,
        ]);
    }

    /**
     * @return string
     */
    public function htmlFoot(): string
    {
        $templateFile = $this->templateDirectory . '/foot.html';
        $template = $this->systemTemplateLoader->loadTranslatedFile($templateFile);

        return $this->readTemplate($template, [
            'LANG' => $this->languageDeterminator->getLanguage(),
        ]);
    }

    /**
     * @param null $pageTitle
     * @param string|null $navBar
     * @param bool $hidePanels
     *
     * @return string
     */
    public function pageStart(?string $pageTitle = null, ?string $navBar = null, bool $hidePanels = false): string
    {
        $templateFile = $this->templateDirectory . ($hidePanels ? '/pagestart_nopanels.html' : '/pagestart.html');
        $template = $this->systemTemplateLoader->loadTranslatedFile($templateFile, ['HEADER']);

        return $this->readTemplate($template, [
            'TEMPLATETITLE' => $this->getPrintedPageTitle($pageTitle),
            'HELPLINK' => 'https://google.com',
            'PAGE' => 'test',
            'LANG' => $this->languageDeterminator->getLanguage(),
            'TOPNAVBAR' => $this->navigationBarBuilder->getNavigationBarHtml(),
            'NAVBARJS' => $this->navigationBarBuilder->getNavigationBarJavaScript(),
        ]);
    }

    /**
     * @return string
     */
    public function pageEnd(): string
    {
        $templateFile = $this->templateDirectory . '/pageend.html';
        $template = $this->systemTemplateLoader->loadTranslatedFile($templateFile, ['POWER', 'UPDATES']);

        return $this->readTemplate($template, [
            'LANG' => $this->languageDeterminator->getLanguage(),
        ]);
    }

    /**
     * @param string|null $pageTitle
     *
     * @return string
     */
    private function getPrintedPageTitle(?string $pageTitle = null): string
    {
        $printedPageTitle = $this->systemConfigurationParser->getNetworkName();
        if ($pageTitle !== null) {
            $printedPageTitle .= ' ' . $pageTitle;
        }

        if (trim($printedPageTitle) === '') {
            $printedPageTitle = 'LoxBerry ' . $this->systemConfigurationParser->getLoxBerryVersion();
        }

        return $printedPageTitle;
    }

    /**
     * @param string $fileName
     * @param array $variables
     *
     * @return string
     */
    private function readTemplate(string $fileName, array $variables = []): string
    {
        if (!file_exists($fileName)) {
            throw new \RuntimeException('Template file does not exist');
        }

        $content = file_get_contents($fileName);
        foreach ($variables as $key => $value) {
            $content = str_replace('<TMPL_VAR ' . $key . '>', $value, $content);
        }

        return $content;
    }
}
