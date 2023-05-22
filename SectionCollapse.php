<?php
/**
 * REDCap External Module: Section Collapse
 * @author Luke Stevens, Murdoch Children's Research Institute
 */
namespace MCRI\SectionCollapse;

use ExternalModules\AbstractExternalModule;

class SectionCollapse extends AbstractExternalModule
{
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
            #em-section-collapse-btn-top-accordion {
                display: none;
            }
        </style>
        <script type="text/javascript">
            $(function(){
                var module = <?=$this->getJavascriptModuleObjectName()?>;
                module.headerCount = 0;
                
                module.icon_expand = '<i class="fas fa-angle-down"></i>';
                module.icon_collapse = '<i class="fas fa-angle-up"></i>';
                module.icon_expand_all = '<i class="fas fa-angle-double-down"></i>';
                module.icon_collapse_all = '<i class="fas fa-angle-double-up"></i>';
                module.icon_accordion_off = '<i class="fas fa-toggle-off"></i>';
                module.icon_accordion_on = '<i class="fas fa-toggle-on"></i>';
                
                module.btn_section       = '<button type="button" class="mx-1 px-2 py-0 btn btn-xs btn-outline-secondary em-section-collapse-btn em-section-collapse-btn-sec" data-section=-1 data-collapse=1>'+module.icon_collapse+'</button>'
                module.btn_top_collapse  = '<button type="button" class="mx-1 px-2 py-0 btn btn-xs btn-outline-secondary em-section-collapse-btn em-section-collapse-btn-top" data-section=0 data-collapse=1 id="em-section-collapse-btn-top-collapse">'+module.icon_collapse_all+'</button>';
                module.btn_top_expand    = '<button type="button" class="mx-1 px-2 py-0 btn btn-xs btn-outline-secondary em-section-collapse-btn em-section-collapse-btn-top" data-section=0 data-collapse=0 id="em-section-collapse-btn-top-expand">'+module.icon_expand_all+'</button>';
                module.btn_top_accordion = '<button type="button" class="mx-1 px-2 py-0 btn btn-xs btn-outline-secondary em-section-collapse-btn em-section-collapse-btn-top" data-accordion-mode=0 id="em-section-collapse-btn-top-accordion">'+module.icon_accordion_off+'</button>';
                module.collapse_top_container = '<div class="my-1 em-section-collapse-all">'+module.btn_top_accordion+module.btn_top_collapse+module.btn_top_expand+'</div>';
                
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
                            $('.em-section-collapse-field.em-section-collapse-'+sectionId).hide();
                        } else { // expand
                            $(this).data('collapse',1).html(module.icon_collapse);

                            // expand all sections
                            $('.em-section-collapse-field').not('.\\@HIDDEN').not('.\\<?=$this->hiddenClass?>').show();
                            doBranching(); // ensure only those fields in the current section that should be visible are visible
                            // now ensure all sections that should be collapsed get collapsed again
                            for (var i = 0; i < module.headerCount; i++) {
                                if ((i+1)===sectionId) continue;
                                var btn = $('button[data-section='+(i+1)+']').first();
                                var collapseState = $(btn).data('collapse');
                                if (collapseState === 0) {
                                    $('.em-section-collapse-field.em-section-collapse-'+(i+1)).hide();
                                }
                            }
                        }
                    }                    
                };

                module.collapse_section = '<div class="em-section-collapse-btndiv">'+module.btn_section+'</div>';

                module.addActionButtons = function() {
                    console.log(module.headerCount+' sections');
                    $('#questiontable').before(module.collapse_top_container);
                    $('.em-section-collapse-header').each(function(i, e) {
                        $(this).find('td.header').find('div[data-mlm-type=header]').addClass('em-section-collapse-headerdiv');
                        $(this).find('td.header').append(module.collapse_section.replace('data-section=-1','data-section='+(i+1)));
                    });
                    $('button.em-section-collapse-btn').on('click', module.action);
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
                    }
                };
                module.initialise();
            });
        </script>
        <!-- SectionCollapse: End -->
        <?php
    }
}