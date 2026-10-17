<?php

namespace Modules\Website\Services;

class PharmaCategoryCardStyle
{
    public function defaults(): array
    {
        return [
            'preset'=>'clinical','font_size'=>14,'radius'=>16,'padding'=>24,'min_height'=>128,
            'gap'=>16,'accent'=>'#047857','background'=>'#ffffff','text_color'=>'#0f172a',
            'border_color'=>'#e2e8f0','show_icon'=>true,'show_count'=>true,'show_arrow'=>true,
            'mobile_columns'=>1,'tablet_columns'=>2,'icon'=>'medical',
            'layout'=>'horizontal','icon_size'=>46,'icon_shadow'=>false,'count_style'=>'subtitle',
            'icons_by_category'=>[],
        ];
    }

    public function normalize(mixed $input): array
    {
        $input=is_array($input)?$input:[];
        $v=$this->defaults();
        $v['preset']=in_array($input['preset']??null,['clinical','compact','soft','minimal','clean','premium'],true)?$input['preset']:$v['preset'];
        $v['layout']=in_array($input['layout']??null,['horizontal','stacked'],true)?$input['layout']:$v['layout'];
        $v['count_style']=in_array($input['count_style']??null,['subtitle','badge'],true)?$input['count_style']:$v['count_style'];
        $v['icon_shadow']=filter_var($input['icon_shadow']??false,FILTER_VALIDATE_BOOLEAN);
        $v['icon_size']=is_numeric($input['icon_size']??null)?max(36,min(64,(int)$input['icon_size'])):$v['icon_size'];
        $v['icon'] = in_array($input['icon'] ?? null, ['medical','heart','shield','pill','cross','leaf','none'], true) ? $input['icon'] : $v['icon'];
        foreach (['font_size'=>[11,20],'radius'=>[0,32],'padding'=>[8,32],'min_height'=>[80,180],'gap'=>[0,32],'mobile_columns'=>[1,2],'tablet_columns'=>[1,3]] as $key=>[$min,$max]) {
            $v[$key]=is_numeric($input[$key]??null)?max($min,min($max,(int)$input[$key])):$v[$key];
        }
        foreach (['accent','background','text_color','border_color'] as $key) {
            $value=$input[$key]??null;
            if(is_string($value)&&preg_match('/^#[0-9a-fA-F]{6}$/D',$value))$v[$key]=strtolower($value);
        }
        foreach (['show_icon','show_count','show_arrow'] as $key) {
            if(array_key_exists($key,$input))$v[$key]=filter_var($input[$key],FILTER_VALIDATE_BOOLEAN);
        }
        $overrides = is_array($input['icons_by_category'] ?? null) ? $input['icons_by_category'] : [];
        foreach (array_slice($overrides, 0, 100, true) as $id => $icon) {
            if (ctype_digit((string) $id) && (int) $id > 0 && in_array($icon, ['medical','heart','shield','pill','cross','leaf','none'], true)) {
                $v['icons_by_category'][(int) $id] = $icon;
            }
        }
        return $v;
    }

    public function css(mixed $input): string
    {
        $v=$this->normalize($input);
        return '--category-icon-size:'.$v['icon_size'].'px;--category-card-font:'.$v['font_size'].'px;--category-card-radius:'.$v['radius'].'px;--category-card-padding:'.$v['padding'].'px;--category-card-height:'.$v['min_height'].'px;--category-card-gap:'.$v['gap'].'px;--category-card-accent:'.$v['accent'].';--category-card-bg:'.$v['background'].';--category-card-text:'.$v['text_color'].';--category-card-border:'.$v['border_color'].';';
    }
}
