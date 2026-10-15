#!/usr/bin/env python3
"""Build private Pro and stripped Free WordPress ZIPs. Never package the source tree directly."""
import argparse, pathlib, re, shutil, subprocess, tempfile, zipfile, hashlib, json
ROOT=pathlib.Path(__file__).resolve().parents[1]
SOURCE=ROOT/'integrations/usp-form-builder'
PRO={'country','file','range','rating','consent','address','admission','intl_tel','payment','page_break','signature','calculation'}
def cut(text,start,end,replacement=''):
    matches=list(re.finditer('^'+re.escape(start),text,re.M))
    assert len(matches)==1, (start,len(matches))
    a=matches[0].start();b=text.index(end,a)
    return text[:a]+replacement+text[b:]
def cases(text):
    pattern=re.compile(r"^                    case '([^']+)':.*?(?=^                    (?:case |default:)|\Z)",re.M|re.S)
    return pattern.sub(lambda m: '' if m[1] in PRO else m[0],text)
def free(root):
    main=root/'usp-form-builder.php'
    s=main.read_text().replace('USP Form Builder Pro','USP Form Builder (Free)').replace('USP Edition:       pro','USP Edition:       free').replace("'USP_FORM_BUILDER_EDITION', 'pro'", "'USP_FORM_BUILDER_EDITION', 'free'")
    s=s.replace("require_once USP_FORM_BUILDER_DIR . 'includes/class-usp-address.php';",'')
    main.write_text(s)
    form=root/'includes/class-usp-form.php';s=form.read_text()
    # Keep explicit recognition of Pro fields to block existing/imported Pro forms safely.
    a=s.index('    public static function get_pro_field_types()');b=s.index('\n    /**',a)
    s=s[:a]+"    public static function get_pro_field_types() { return array( "+', '.join(repr(p) for p in sorted(PRO))+" ); }\n"+s[b:]
    a=s.index('    public static function get_countries()');b=s.index('\n    /**',a)
    s=s[:a]+"    public static function get_countries() { return array(); }\n    public static function is_valid_country_value( $value ) { return false; }\n"+s[b:]
    a=s.index('    public static function get_supported_field_types()');b=s.index('\n    /**',a)
    defs=s[a:b]
    pattern=re.compile(r"^            '([^']+)' => array\(.*?(?=^            '[^']+' => array\(|^        \);)",re.M|re.S)
    defs=pattern.sub(lambda m: '' if m[1] in PRO else m[0],defs)
    s=s[:a]+defs+s[b:]
    s=cut(s,'    public static function get_country_phone_rules()', '    public static function render_form_html', '    public static function get_country_phone_rules() { return array(); }\n\n')
    s=cut(s,'        // Multi-Step Wizard Partitioning','        $container_classes', '        $has_steps = false;\n\n')
    a=s.index('                <?php if ( $has_steps ) : ?>');b=s.index('                    <!-- Single-Page Standard Fields Grid -->',a)
    s=s[:a]+s[b:]
    a=s.index('                    <!-- Single-Page Standard Fields Grid -->');b=s.index('<?php endif; ?>',a)
    s=s[:b]+s[b:].replace('<?php endif; ?>','',1)
    s=cut(s,'        // Page break divider (if rendered standalone)', '        $width_class')
    s=cases(s);form.write_text(s)
    ajax=root/'includes/class-usp-ajax.php';s=ajax.read_text()
    a=s.index('    public function ajax_submit_form()')
    s=s[:a]+cut(s[a:],"            if ( 'address' === $type ) {",'            // Standard inputs')
    s=cut(s,"            if ( 'country' === $type &&", "            if ( 'cnic' === $type ) {")
    s=cases(s.replace("                    case 'range':\n",''));ajax.write_text(s)
    public=root/'public/class-usp-public.php';s=public.read_text()
    s=cut(s,"        wp_enqueue_style( 'usp-country-css'",'        wp_enqueue_script(\n')
    s=s.replace("array( 'jquery', 'usp-country-js', 'usp-address-js' )","array( 'jquery' )")
    public.write_text(s)
    admin=root/'admin/class-usp-admin.php';s=admin.read_text()
    s=cut(s,"            wp_enqueue_style( 'usp-country-css'",'            wp_enqueue_script(\n')
    s=s.replace("array( 'jquery', 'usp-admin-js', 'usp-country-js', 'usp-address-js' )", "array( 'jquery', 'usp-admin-js' )")
    s=s.replace("USP_Address::preview_template()", "''")
    admin.write_text(s)
    (root/'includes/class-usp-address.php').unlink()
    (root/'includes/countries.php').unlink()
    shutil.rmtree(root/'assets')
    shutil.copyfile(ROOT/'integrations/free-runtime/usp-public.js',root/'public/assets/js/usp-public.js')
    builder=root/'admin/assets/js/usp-builder.js'
    subprocess.run(['node',str(ROOT/'scripts/strip-pro-builder.cjs'),str(builder),str(builder)],check=True,cwd=ROOT)
    # Safety checks fail the build if premium backend handlers accidentally return.
    for token in ['case \'signature\':','case \'address\':','case \'calculation\':', 'USP_Address::', 'data:image/png;base64']:
        assert token not in form.read_text() and token not in ajax.read_text(), token
    assert 'USP_Address::' not in admin.read_text()
    assert 'initSignaturePads' not in (root/'public/assets/js/usp-public.js').read_text()
    (root/'readme.txt').write_text('''=== USP Form Builder (Free) ===
Contributors: waseem
Requires at least: 6.3
Requires PHP: 7.4
Stable tag: 1.1.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Visual form builder with standard fields, conditional visibility, entries, email notifications and anti-spam protection.
Pro field renderers, submission handlers, location datasets and frontend engines are excluded from this package.
Buy a subscription at https://usp.com.pk/products/usp-form-builder, activate the key in Pro & Security Hub, then choose Install Pro files.
Pro replaces this plugin in place using the WordPress upgrader. Existing forms and settings are preserved.
An active subscription is required for Pro forms to accept submissions. Free forms remain available without a subscription.
''')
def main():
    args=argparse.ArgumentParser();args.add_argument('--output',default=str(ROOT/'dist/plugins'));out=pathlib.Path(args.parse_args().output);out.mkdir(parents=True,exist_ok=True)
    manifest=[]
    for edition in ['free','pro']:
        with tempfile.TemporaryDirectory(prefix='usp-plugin-') as temp:
            root=pathlib.Path(temp)/'usp-form-builder'
            shutil.copytree(SOURCE,root,ignore=shutil.ignore_patterns('tests','.git','*.md','.DS_Store'))
            if edition=='free': free(root)
            for file in root.rglob('*.php'):
                subprocess.run(['php','-l',str(file)],stdout=subprocess.DEVNULL,check=True)
            for file in root.rglob('*.js'):
                subprocess.run(['node','--check',str(file)],stdout=subprocess.DEVNULL,check=True)
            name='usp-form-builder-1.1.3-'+edition+'.zip';target=out/name
            with zipfile.ZipFile(target,'w',zipfile.ZIP_DEFLATED) as archive:
                for file in sorted(root.rglob('*')):
                    if file.is_file(): archive.write(file,file.relative_to(root.parent))
            manifest.append({'file':name,'edition':edition,'version':'1.1.3','sha256':hashlib.sha256(target.read_bytes()).hexdigest(),'bytes':target.stat().st_size})
    (out/'manifest.json').write_text(json.dumps(manifest,indent=2)+'\n')
    print(json.dumps(manifest,indent=2))
if __name__=='__main__':main()
