const crypto = require('crypto');
const readline = require('readline');

// RSA 私钥 (PKCS#8) - 与 Kernel.php 中的公钥配对
// ⚠️ 此文件仅开发者使用，绝不发给客户！
const PRIVATE_KEY = `-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCTgWwrhFWH8YOl
K5bPytVa3I4wVTJ1mZwXH4Mk/Oaonm13yDn8q7Nue08dV1A/4i9tvUNaH9kUwcAy
ogn3K0rLp8UJX4cVUplm+w2epnTGlen96D3BKvFNzejhkY+sGXfBileI8UqiYvyn
np7ojf9UcyTmIBqZV/ysJC0u48lqVT9JEzSmnIvzp1vAX5p2xGk1mlMzsDRch8kL
NxLNW5mxY36/9XFuJjl0xAM+vWMqdunNtseyv4ilNijwC6QDyJuEXf+UCW+22X7s
Cm1hNlDe4KHLI0MSKqjA/IOvQbix5a6Zy3QqVZgxct+kQz0AgXYBLr0ss+C64CI3
Oqp3puOVAgMBAAECggEAAzR8tTVa5mlJAv2/eSRfHmpMbKBtSEDeM2Jr5xHXsTJA
Dqo7GFJiw7PKNEkBZWH0xd2m6P5oKRtMoc0DimtWZLZwgZOhy8TPsH0j/p5/SxFW
1D2Hmfrk41vmA4C2LncfMwVegYs8o+qissGvkj4DnkIiOx3Xdh2DT/o5QRuR9M18
8KeX3ov7xDc+johFrEswM3kQa9g86L7XUuRN+pvr+ZgVmN8UUV8i3kmAsd4jT0+/
a50gnS6b3CoA+tkmRZff2t5ktkQMA7WSud/QQkWfO+m8d3w8Rlb1WyoZ2wfX0hIh
ZhK+lhy34X37AiLvqirhqTAp8fLvEVeWel5OxJIcAQKBgQDDf+lni6lQz448kkyU
7oLdm5iWMCOp66QEUA7LxefHQIWOukAFsT5rsK+9/N6VzyzfFWcNWplRGIF3YPZF
o9SUC6/DmRIdl4+tQedc1FiPvWdNfLZ5U1MF91trwOgBrJzrGiQsWiM9WEVZX98t
wqwfUlGqegrk/WuKXjySKIw8ZQKBgQDBJ0UIZ+ufx4MM4g9jof0kSq2fXgmHY0WN
xJKMQhO3j8cSXglXhaICASLV6OlggwJcF9p5NcWri6bTNBgLL2M6wOfWd/QI0VcL
SsJSVShFK13vaZ/jK5B/451QiV70hO74rO5aWxu0Vq+XgSo1J811l4Y3thj6+KnW
xt+wPbAfcQKBgQCdTH8FRFdAYTAFxoT91AbRvg8A0MzxPFsk6TFufdcm9+A+XdeD
fQBHb8uHpz2E9kQZXsQi94GiwX/yCL8+ezpgwCJ00+XCj58X/Y3loCl9xhynOTAs
4IFNrKAPTBCgwD0l4Uu3r/LouexapAwTaq5JKVfyPe4kH0927qn3w85KDQKBgB6e
cAYOGz7/7JldOJ1Nr4hSQkWyLYaFyR/ZGnYbf26o3yDdQVPbfjwdG/2W53ACNJyO
t3ZhfM1d3Ps8FdeoEm3c6KOJ7mh7bvX+SuGsWC7m2gQ61Tq4zDZJycVbkW7np85J
85rlOHYVC87TjfJRNfO/KmqodjtKsfm3GvygOswxAoGAZKys/vQm9diatyxRxct/
r2qsWx5DpcwuyU2PoilcaIM5IbWpt3VpQZzogiPhCJ7uVfRvtJ+ZZ8zrq0VR7oXW
nofIadcHY4U+Ce7cQR2ONoRbZoA9rUnfYCM4AvP6dL2rM0Xtrd7QI6YsmL0saOb4
aLiupXK4Yrz5iwgQueHOVc4=
-----END PRIVATE KEY-----`;

function generateLicense(domain) {
    // 标准化域名
    domain = domain.toLowerCase().trim();
    domain = domain.replace(/^https?:\/\//, '');
    domain = domain.replace(/^www\./, '');
    domain = domain.replace(/\/.*$/, '');

    // 构造授权数据
    const data = domain;

    // RSA-SHA256 签名
    const sign = crypto.createSign('RSA-SHA256');
    sign.update(data);
    const signature = sign.sign(PRIVATE_KEY, 'base64url');

    // 授权码 = 域名:完整签名
    const license = domain + ':' + signature;

    return {
        domain: domain,
        license: license
    };
}

// 命令行模式
if (process.argv.length > 2) {
    const domain = process.argv[2];
    const result = generateLicense(domain);

    console.log('');
    console.log('==========================================');
    console.log('       Website License Generator');
    console.log('==========================================');
    console.log('');
    console.log('  Domain  : ' + result.domain);
    console.log('  License : ' + result.license);
    console.log('');
    console.log('==========================================');
    console.log('');
    console.log('Add this to client config/config.php:');
    console.log("  'sn' => '" + result.license + "',");
    console.log('');
} else {
    // 交互模式
    const rl = readline.createInterface({
        input: process.stdin,
        output: process.stdout
    });

    console.log('');
    console.log('==========================================');
    console.log('       Website License Generator');
    console.log('==========================================');
    console.log('');

    rl.question('Enter client domain (e.g. example.com): ', function(domain) {
        if (!domain.trim()) {
            console.log('');
            console.log('[ERROR] Domain cannot be empty!');
            console.log('');
            rl.close();
            return;
        }

        var result = generateLicense(domain);

        console.log('');
        console.log('==========================================');
        console.log('        License Generation Result');
        console.log('==========================================');
        console.log('');
        console.log('  Domain  : ' + result.domain);
        console.log('  License : ' + result.license);
        console.log('');
        console.log('==========================================');
        console.log('');
        console.log('Add this to client config/config.php:');
        console.log("  'sn' => '" + result.license + "',");
        console.log('');
        console.log('Press Enter to exit...');

        rl.close();
    });
}
