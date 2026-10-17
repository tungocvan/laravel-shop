<?php
namespace Modules\Website\Services;

class HeaderNavigationStyleService
{
    public function defaults(): array
    {
        return ['font_family'=>'system','font_size'=>14,'font_weight'=>600,'padding_x'=>12,'padding_y'=>8,'gap'=>12,'radius'=>8,'style'=>'classic','text_color'=>'#334155','active_color'=>'#047857','active_background'=>'#d1fae5','hover_color'=>'#047857','hover_background'=>'#f1f5f9'];
    }

    public function resolve(mixed $input): array
    {
        $defaults=$this->defaults();
        $input=is_array($input)?$input:[];
        $result=$defaults;
        foreach (['font_size'=>[11,24],'font_weight'=>[400,700],'padding_x'=>[0,32],'padding_y'=>[0,24],'gap'=>[0,32],'radius'=>[0,24]] as $key=>$bounds) {
            $result[$key]=is_numeric($input[$key]??null)?max($bounds[0],min($bounds[1],(int)$input[$key])):$defaults[$key];
        }
        if (!in_array($result['font_weight'],[400,500,600,700],true)) $result['font_weight']=600;
        foreach (['font_family'=>['system','arial','inter','roboto','georgia'],'style'=>['classic','pills','underline']] as $key=>$allowed) {
            $result[$key]=in_array($input[$key]??null,$allowed,true)?$input[$key]:$defaults[$key];
        }
        foreach (['text_color','active_color','active_background','hover_color','hover_background'] as $key) {
            $value=$input[$key]??null;
            $result[$key]=is_string($value)&&preg_match('/^#[a-fA-F0-9]{6}$/D',$value)?strtolower($value):$defaults[$key];
        }
        return $result;
    }

    public function css(mixed $input): string
    {
        $v=$this->resolve($input);
        $font=match($v['font_family']){'arial'=>'Arial, sans-serif','inter'=>'Inter, Arial, sans-serif','roboto'=>'Roboto, Arial, sans-serif','georgia'=>'Georgia, serif',default=>'system-ui, sans-serif'};
        return '--nav-font:'.$font.';--nav-size:'.$v['font_size'].'px;--nav-weight:'.$v['font_weight'].';--nav-px:'.$v['padding_x'].'px;--nav-py:'.$v['padding_y'].'px;--nav-gap:'.$v['gap'].'px;--nav-radius:'.$v['radius'].'px;--nav-text:'.$v['text_color'].';--nav-active:'.$v['active_color'].';--nav-active-bg:'.$v['active_background'].';--nav-hover:'.$v['hover_color'].';--nav-hover-bg:'.$v['hover_background'].';';
    }
}
