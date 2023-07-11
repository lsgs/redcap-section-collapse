<?php
/**
 * REDCap External Module: Section Collapse
 * @author Luke Stevens, Murdoch Children's Research Institute
 */
namespace MCRI\SectionCollapse;

use ExternalModules\AbstractExternalModule;

class SectionCollapse extends AbstractExternalModule
{
    protected const DEFAULT_MODE = 'ab'; // both "accordion" and "by section" allowed
    protected const ACCORDION_MODE = 'a';
    protected const BYSECTION_MODE = 'b';
    protected const DISABLED_MODE = 'd';
    protected const USER_PREF_ACTION = 'user-mode-preference';
    protected $isSurvey = false;
    protected $instrument = '';
    protected $hiddenClass = '-';

    public function redcap_data_entry_form($project_id, $record, $instrument, $event_id, $group_id, $repeat_instance) {
        $this->isSurvey = false;
        $this->instrument = $instrument;
        $this->hiddenClass = '@HIDDEN-FORM';
        $this->pageTop();
    }

    public function redcap_survey_page($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance) {
        $this->isSurvey = true;
        $this->instrument = $instrument;
        $this->hiddenClass = '@HIDDEN-SURVEY';
        $this->pageTop();
    }

    protected function pageTop() {
        $modeOptions = $this->getProjectSetting('project-default') ?? static::DEFAULT_MODE;
        $overrideInstruments = $this->getProjectSetting('instrument');
        $overrideInstrumentModes = $this->getProjectSetting('instrument-mode');
        foreach ($overrideInstruments as $ikey => $iname) {
            if ($iname===$this->instrument) {
                $modeOptions = $overrideInstrumentModes[$ikey];
                break;
            }
        }
        if ($modeOptions===static::DISABLED_MODE) return; // switched off for current instrument
        if ($this->isSurvey) {
            $userModePref = (array_key_exists(static::USER_PREF_ACTION, $_COOKIE)) ? $_COOKIE[static::USER_PREF_ACTION] : '';
        } else {
            $userModePref = $this->getUserSetting(static::USER_PREF_ACTION) ?? '';
        }
        $userModePref = (str_contains($modeOptions, $userModePref)) ? $userModePref : '';

        $this->initializeJavascriptModuleObject();
        ?>
        <!-- SectionCollapse: Begin -->
        <style type="text/css">
            .em-section-collapse-all {
                display:inline-block;
                width:100%;
                text-align: right;
            } 
            .em-section-collapse-headerdiv { /*[data-mlm-type=header] */
                display:inline-block;
                width:90%;
            }
            .em-section-collapse-btndiv {
                display:inline-block;
                width:10%;
                text-align: right;
            } 
            .em-section-collapse-btn {
                color: 'inherit';
            }
            .em-section-collapse-tr-collapsed {
                visibility: collapse;
            }
            #em-section-collapse-bysectiondiv {
                display: inline;
            }
        </style>
        <script type="text/javascript">
            $(function(){
                var module = <?=$this->getJavascriptModuleObjectName()?>;
                module.headerCount = 0;
                module.allowAccordion = <?=(str_contains($modeOptions,static::ACCORDION_MODE))?1:0?>;
                module.allowBySection = <?=(str_contains($modeOptions,static::BYSECTION_MODE))?1:0?>;
                module.userModePref = '<?=$userModePref?>';
                module.accordionMode = (module.allowAccordion && (!module.allowBySection || module.userModePref == '<?=static::ACCORDION_MODE?>')) ? 1 : 0;

                module.icon_expand = '<i class="fas fa-angle-down"></i>';
                module.icon_collapse = '<i class="fas fa-angle-up"></i>';
                module.icon_expand_all = '<i class="fas fa-angle-double-down"></i>';
                module.icon_collapse_all = '<i class="fas fa-angle-double-up"></i>';
                module.icon_accordion_off = '<i class="fas fa-toggle-off"></i>';
                module.icon_accordion_on = '<i class="fas fa-toggle-on"></i>';
                
                module.btn_section       = '<button type="button" class="mx-1 px-2 py-0 btn btn-xs btn-outline-secondary d-print-none em-section-collapse-btn em-section-collapse-btn-sec" data-section=-1 data-collapse=1>'+module.icon_collapse+'</button>'
                module.btn_top_collapse  = '<button type="button" class="mx-1 px-2 py-0 btn btn-xs btn-outline-secondary d-print-none em-section-collapse-btn em-section-collapse-btn-top" data-section=0 data-collapse=1 id="em-section-collapse-btn-top-collapse">'+module.icon_collapse_all+'</button>';
                module.btn_top_expand    = '<button type="button" class="mx-1 px-2 py-0 btn btn-xs btn-outline-secondary d-print-none em-section-collapse-btn em-section-collapse-btn-top" data-section=0 data-collapse=0 id="em-section-collapse-btn-top-expand">'+module.icon_expand_all+'</button>';
                module.btn_top_accordion = '<button type="button" class="mx-1 px-2 py-0 btn btn-xs btn-outline-secondary d-print-none" title="Accordion mode" id="em-section-collapse-btn-top-accordion">'+module.icon_accordion_off+'</button>';
                module.collapse_top_container = '<div class="my-1 em-section-collapse-all">'+module.btn_top_accordion+'<div id="em-section-collapse-bysectiondiv">'+module.btn_top_collapse+module.btn_top_expand+'</div></div>';
                module.collapse_section = '<div class="em-section-collapse-btndiv">'+module.btn_section+'</div>';

                module.action = function() {
                    var collapse = $(this).data('collapse');
                    var collapseAction = (collapse) ? 'collapse' : 'expand';
                    var sectionId = $(this).data('section');
                    
                    if (sectionId===0) { // expand/collapse all
                        console.log(collapseAction+' all');
                        for (var i = 0; i < module.headerCount; i++) {
                            var sectionBtn = $('button[data-section='+(i+1)+']').first();
                            if ($(sectionBtn).data('collapse') == collapse) {
                                // if needs opposite collapse only!
                                $(sectionBtn).trigger('click');
                            }
                        }
                    } else {
                        console.log(collapseAction+' '+sectionId);
                        if (collapse) {
                            $(this).data('collapse',0).html(module.icon_expand);
                            $('.em-section-collapse-field.em-section-collapse-'+sectionId).addClass('em-section-collapse-tr-collapsed');
                        } else { // expand
                            if (module.accordionMode) {
                                // accordion mode - close all before expanding this section
                                $('#em-section-collapse-btn-top-collapse').trigger('click');
                            }
                            $(this).data('collapse',1).html(module.icon_collapse);
                            $('.em-section-collapse-field.em-section-collapse-'+sectionId).removeClass('em-section-collapse-tr-collapsed');
                        }
                    }                    
                };

                module.addActionButtons = function() {
                    console.log(module.headerCount+' sections');
                    $('#questiontable').before(module.collapse_top_container);
                    $('.em-section-collapse-header').each(function(i, e) {
                        $(this).find('td.header').find('div[data-mlm-type=header]').addClass('em-section-collapse-headerdiv');
                        $(this).find('td.header').append(module.collapse_section.replace('data-section=-1','data-section='+(i+1)));
                    });
                    $('button.em-section-collapse-btn').on('click', module.action);
                };

                module.toggleAccordionMode = function(activate) {
                    if (activate) {
                        module.accordionMode = 1;
                        $('#em-section-collapse-bysectiondiv').hide();
                        $('#em-section-collapse-btn-top-accordion').html(module.icon_accordion_on);
                        $('#em-section-collapse-btn-top-collapse').trigger('click'); // collapse all
                    } else {
                        module.accordionMode = 0;
                        $('#em-section-collapse-bysectiondiv').show();
                        $('#em-section-collapse-btn-top-accordion').html(module.icon_accordion_off);
                    }
                };

                module.initialise = function() {
                    $('#questiontable tr')
                        .filter('[id$=-tr]')
                        .not('[id=<?=$this->instrument?>_complete-sh-tr]')
                        .not('[id=<?=$this->instrument?>_complete-tr]')
                        .not('[id$=__-tr]')
                        .each(function(){
                            var thisid = $(this).attr('id');
                            var isHeader = (thisid.endsWith('-sh-tr'));
                            if (isHeader) {
                                module.headerCount++;
                                $(this).addClass('em-section-collapse-header');
                                $(this).addClass('em-section-collapse-'+module.headerCount);
                            } else {
                                $(this).addClass('em-section-collapse-field');
                                $(this).addClass('em-section-collapse-'+module.headerCount);
                            }
                            $(this).data('section', module.headerCount);
                    });
                    if (module.headerCount > 1) {
                        module.addActionButtons(); // only add functionality if 2 or more sections
                        if (module.allowAccordion) {
                            module.toggleAccordionMode(module.accordionMode);
                            if (module.allowBySection) {
                                $('#em-section-collapse-btn-top-accordion').on('click', function() {
                                    module.toggleAccordionMode(!module.accordionMode); // only have accordion btn do anything if alternative allowed
                                    var pref = (module.accordionMode) ? '<?=static::ACCORDION_MODE?>' : '<?=static::BYSECTION_MODE?>';
                                    <?php
                                    if ($this->isSurvey) {
                                    ?>
                                    document.cookie = '<?=static::USER_PREF_ACTION?>='+pref;
                                    <?php
                                    } else {
                                    ?>
                                    module.ajax('<?=static::USER_PREF_ACTION?>', pref).then(function(response) {
                                        console.log('user collapse preference '+pref+((response)?' saved':' save failed'));
                                    }).catch(function(err) {
                                        console.log('user collapse preference '+pref+' save failed: '+err);
                                    });
                                    <?php
                                    }
                                    ?>
                                });
                            } else {
                                $('#em-section-collapse-btn-top-accordion').prop('disabled', true);
                            }
                        } else {
                            $('#em-section-collapse-btn-top-accordion').hide();
                            module.toggleAccordionMode(false);
                        }
                    }
                };
                module.initialise();
            });
        </script>
        <!-- SectionCollapse: End -->
        <?php
    }

    public function redcap_module_ajax($action, $payload, $project_id, $record, $instrument, $event_id, $repeat_instance, $survey_hash, $response_id, $survey_queue_hash, $page, $page_full, $user_id, $group_id) {
        if ($action != static::USER_PREF_ACTION) return 0;
        $return = 0;
        switch ($payload) {
            case static::ACCORDION_MODE: 
            case static::BYSECTION_MODE: 
                $return = 1;
                $this->setUserSetting(static::USER_PREF_ACTION, $payload);
                break;
            default: break;
        }
        return $return;
    }
}