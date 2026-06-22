const fs = require('fs');
const path = require('path');

const root = 'D:/其他项目代码/MK独立官网版';
const indexPath = path.join(root, 'index.php');
const adminPath = path.join(root, 'admin.php');
const cssPath = path.join(root, 'assets/style.css');
const dataPath = path.join(root, 'data/site.json');
const contactDir = path.join(root, 'assets/contact');

const images = {
  'instagram.jpg': 'D:/Program Files/xwechat_files/wxid_k5syosar4vm522_ea52/temp/RWTemp/2026-06/561bd0c8d7b53c9748060718cbc2d257/fe1e2b51e7841c12e84e9276b1ec6cb6.jpg',
  'wechat.jpg': 'D:/Program Files/xwechat_files/wxid_k5syosar4vm522_ea52/temp/RWTemp/2026-06/561bd0c8d7b53c9748060718cbc2d257/51522a77ae11f07e71bb9d55792ec584.jpg',
  'whatsapp.jpg': 'D:/Program Files/xwechat_files/wxid_k5syosar4vm522_ea52/temp/RWTemp/2026-06/561bd0c8d7b53c9748060718cbc2d257/4466ab176e84817e3f729d46f68e279f.jpg',
  'linkedin.jpg': 'D:/Program Files/xwechat_files/wxid_k5syosar4vm522_ea52/temp/RWTemp/2026-06/561bd0c8d7b53c9748060718cbc2d257/db7d79fa3bd15802946e2471dca5d96b.jpg',
};

function mustRead(file) {
  if (!fs.existsSync(file)) throw new Error(`Missing file: ${file}`);
  return fs.readFileSync(file, 'utf8');
}

fs.mkdirSync(contactDir, { recursive: true });
for (const [name, source] of Object.entries(images)) {
  if (!fs.existsSync(source)) {
    console.warn(`QR image missing, skipped: ${source}`);
    continue;
  }
  fs.copyFileSync(source, path.join(contactDir, name));
}

const data = JSON.parse(mustRead(dataPath));
data.site = data.site || {};
data.site.email = 'brian@mklighting.cn';
data.contact = Object.assign({
  title: 'Contact MK Modern King',
  image: '/assets/mk/contact.jpg',
  map_embed: '',
}, data.contact || {}, {
  email: 'brian@mklighting.cn',
  instagram_url: 'https://www.instagram.com/mordenkey/',
  instagram_qr: '/assets/contact/instagram.jpg',
  wechat_label: 'Brian Zhu',
  wechat_qr: '/assets/contact/wechat.jpg',
  whatsapp_url: '',
  whatsapp_qr: '/assets/contact/whatsapp.jpg',
  linkedin_url: '',
  linkedin_qr: '/assets/contact/linkedin.jpg',
});
fs.writeFileSync(dataPath, JSON.stringify(data, null, 4).replace(/\n/g, '\r\n'));

let index = mustRead(indexPath);
if (!index.includes('function connect_item')) {
  const insert = `
function connect_item($label,$icon,$url,$qr,$hint){
    $has_url = trim((string)$url) !== '';
    $tag = $has_url ? 'a' : 'div';
    $href = $has_url ? ' href="'.h($url).'" target="_blank" rel="noopener"' : '';
    echo '<'.$tag.' class="connect-card"'.$href.'><span class="connect-icon">'.h($icon).'</span><span class="connect-copy"><b>'.h($label).'</b><small>'.h($hint).'</small></span>';
    if(trim((string)$qr) !== '') echo '<img src="'.h($qr).'" alt="'.h($label).' QR code">';
    echo '</'.$tag.'>';
}
function connect_widget($data){
    $contact = $data['contact'] ?? [];
    $email = $contact['email'] ?? ($data['site']['email'] ?? '');
?>
<div class="connect-widget" aria-live="polite">
    <button class="connect-toggle" type="button" aria-expanded="false" aria-controls="connectPanel"><span>+</span><b>Connect</b></button>
    <div class="connect-backdrop" data-connect-close hidden></div>
    <aside class="connect-panel" id="connectPanel" hidden>
        <button class="connect-close" type="button" data-connect-close aria-label="Close">&times;</button>
        <div class="lux-kicker">Quick contact</div>
        <h2>Connect with Brian</h2>
        <p>Choose the fastest channel for catalogues, project drawings and quotation support.</p>
        <div class="connect-grid">
            <?php connect_item('Instagram','IG',$contact['instagram_url'] ?? '',$contact['instagram_qr'] ?? '','Open profile or scan'); ?>
            <?php connect_item('WeChat','WX','',$contact['wechat_qr'] ?? '',$contact['wechat_label'] ?? 'Scan to add'); ?>
            <?php connect_item('WhatsApp','WA',$contact['whatsapp_url'] ?? '',$contact['whatsapp_qr'] ?? '','Open chat or scan'); ?>
            <?php connect_item('LinkedIn','IN',$contact['linkedin_url'] ?? '',$contact['linkedin_qr'] ?? '','Open profile or scan'); ?>
            <?php connect_item('Email','@',$email ? 'mailto:'.$email : '', '', $email ?: 'Send inquiry'); ?>
        </div>
    </aside>
</div>
<script>
(function(){
  var panel=document.getElementById('connectPanel');
  var btn=document.querySelector('.connect-toggle');
  var backdrop=document.querySelector('.connect-backdrop');
  if(!panel||!btn||!backdrop) return;
  function setOpen(open){ panel.hidden=!open; backdrop.hidden=!open; btn.setAttribute('aria-expanded', open?'true':'false'); document.body.classList.toggle('connect-open', open); }
  btn.addEventListener('click', function(){ setOpen(panel.hidden); });
  document.querySelectorAll('[data-connect-close]').forEach(function(el){ el.addEventListener('click', function(){ setOpen(false); }); });
  document.addEventListener('keydown', function(e){ if(e.key==='Escape') setOpen(false); });
})();
</script>
<?php }
`;
  index = index.replace('function footer_html($site){ global $sent; ?>', insert + '\nfunction footer_html($site){ global $sent,$data; ?>');
}
if (!index.includes('connect_widget($data);')) {
  index = index.replace('<?php if(!empty($sent)){ ?><script>alert("Thank you. Your inquiry has been submitted.");</script><?php } ?></body></html>', '<?php connect_widget($data); if(!empty($sent)){ ?><script>alert("Thank you. Your inquiry has been submitted.");</script><?php } ?></body></html>');
}
fs.writeFileSync(indexPath, index);

let admin = mustRead(adminPath);
if (!admin.includes('instagram_url')) {
  admin = admin.replace(
    "foreach(['title','image','map_embed'] as $k) $data['contact'][$k]=$_POST[$k]??''; if($u=upload_file('image_file')) $data['contact']['image']=$u;",
    "foreach(['title','image','map_embed','email','instagram_url','instagram_qr','wechat_label','wechat_qr','whatsapp_url','whatsapp_qr','linkedin_url','linkedin_qr'] as $k) $data['contact'][$k]=$_POST[$k]??''; if($u=upload_file('image_file')) $data['contact']['image']=$u; foreach(['instagram_qr','wechat_qr','whatsapp_qr','linkedin_qr'] as $k){ if($u=upload_file($k.'_file')) $data['contact'][$k]=$u; }"
  );
  admin = admin.replace(
    "text_input('title',$data['contact']['title']??'','页面标题'); image_field('image',$data['contact']['image']??'','背景图片'); text_area('map_embed',$data['contact']['map_embed']??'','地图嵌入代码'); ?><button>保存</button>",
    "text_input('title',$data['contact']['title']??'','页面标题'); image_field('image',$data['contact']['image']??'','背景图片'); text_input('email',$data['contact']['email']??($data['site']['email']??''),'建联邮箱'); text_input('instagram_url',$data['contact']['instagram_url']??'','Instagram 链接'); image_field('instagram_qr',$data['contact']['instagram_qr']??'','Instagram 二维码'); text_input('wechat_label',$data['contact']['wechat_label']??'','微信显示名称'); image_field('wechat_qr',$data['contact']['wechat_qr']??'','微信二维码'); text_input('whatsapp_url',$data['contact']['whatsapp_url']??'','WhatsApp 链接'); image_field('whatsapp_qr',$data['contact']['whatsapp_qr']??'','WhatsApp 二维码'); text_input('linkedin_url',$data['contact']['linkedin_url']??'','LinkedIn 链接'); image_field('linkedin_qr',$data['contact']['linkedin_qr']??'','LinkedIn 二维码'); text_area('map_embed',$data['contact']['map_embed']??'','地图嵌入代码'); ?><button>保存</button>"
  );
}
fs.writeFileSync(adminPath, admin);

let css = mustRead(cssPath);
if (!css.includes('.connect-widget')) {
  css += `
.connect-widget{position:fixed;right:24px;bottom:24px;z-index:80}.connect-toggle{border:0;background:#c6a56a;color:#111;min-height:54px;padding:0 18px;border-radius:999px;display:flex;align-items:center;gap:10px;font:600 12px/1 Inter,Arial,sans-serif;text-transform:uppercase;letter-spacing:.1em;box-shadow:0 18px 45px rgba(0,0,0,.28);cursor:pointer}.connect-toggle span{width:26px;height:26px;border-radius:50%;display:grid;place-items:center;background:#111;color:#fff;font-size:18px;line-height:1}.connect-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.42);backdrop-filter:blur(4px);z-index:79}.connect-panel{position:fixed;right:24px;bottom:92px;width:min(420px,calc(100vw - 32px));max-height:calc(100vh - 120px);overflow:auto;background:#f6f1e8;color:#11100e;z-index:81;padding:28px;border:1px solid rgba(0,0,0,.12);box-shadow:0 30px 80px rgba(0,0,0,.36)}.connect-panel h2{font-family:"Cormorant Garamond",Georgia,serif;font-weight:400;font-size:42px;line-height:1;margin:0 36px 12px 0}.connect-panel p{color:#665f56;line-height:1.7;margin:0 0 20px}.connect-close{position:absolute;right:16px;top:14px;border:0;background:#111;color:#fff;width:34px;height:34px;border-radius:50%;font-size:24px;line-height:1;cursor:pointer}.connect-grid{display:grid;gap:12px}.connect-card{display:grid;grid-template-columns:44px 1fr 96px;gap:14px;align-items:center;padding:12px;background:#fff;border:1px solid rgba(0,0,0,.1);color:#111}.connect-card:hover{border-color:#c6a56a}.connect-icon{width:44px;height:44px;border-radius:50%;display:grid;place-items:center;background:#111;color:#fff;font-size:12px;font-weight:700;letter-spacing:.06em}.connect-copy{display:grid;gap:4px}.connect-copy b{font-size:15px}.connect-copy small{color:#746b60;font-size:12px;line-height:1.35}.connect-card img{width:96px;height:96px;object-fit:cover;background:#fff;border:1px solid rgba(0,0,0,.08)}@media(max-width:620px){.connect-widget{right:16px;bottom:16px}.connect-toggle b{display:none}.connect-panel{left:16px;right:16px;bottom:82px;width:auto;padding:22px}.connect-panel h2{font-size:34px}.connect-card{grid-template-columns:42px 1fr}.connect-card img{grid-column:1/-1;width:100%;height:auto;max-height:220px;object-fit:contain}}`;
}
fs.writeFileSync(cssPath, css);

console.log('MK connect widget has been applied.');
