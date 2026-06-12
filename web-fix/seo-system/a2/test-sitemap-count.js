require('dotenv').config();
const { generateSitemapXml, fetchSettings } = require('./scripts/sitemap-generator');

fetchSettings()
  .then((s) => generateSitemapXml(s))
  .then((xml) => {
    const urls = (xml.match(/<url>/g) || []).length;
    console.log('URL_COUNT', urls);
  })
  .catch((e) => {
    console.error('ERR', e.message);
    process.exit(1);
  });
