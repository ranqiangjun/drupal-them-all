#!/usr/bin/env node
const request = require('request');
const cheerio = require('cheerio');
const chunk = require('chunk');
const jsonfile = require('jsonfile');
const fs = require('fs');

const filenames = {
    'modules': '.dta/modules.json',
    'themes': '.dta/themes.json',
    'distributions': '.dta/distributions.json',
    'packages_local': '.dta/packages_local.json',
    'packages_remote': '.dta/packages_remote.json',
    'packages_ignore': '.dta/packages_ignore.json',
    'packages_new': '.dta/packages_new.json',
    'contributions_commands': '.dta/contributions_commands.sh',
    'composer': '.dta/composer.json',
    'packages_dev': '.dta/dev.json',
    'packages_alpha': '.dta/alpha.json',
    'packages_beta': '.dta/beta.json',
    'packages_rc': '.dta/rc.json',
};

var url_modules = 'https://www.drupal.org/project/project_module/index?project-status=full&drupal_core=7234';
var url_themes = 'https://www.drupal.org/project/project_theme/index?project-status=full&drupal_core=7234';
var url_distributions = 'https://www.drupal.org/project/project_distribution/index?project-status=full&drupal_core=7234';

var headers = {
    'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36',
    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8'
};

// Get all remote packages.
var getRemoteAll = function () {
    'use strict';
    request({
        url: url_modules,
        headers: headers
    }, function (error, response, body) {
        callback(error, response, body, filenames['modules']);
    });

    request({
        url: url_themes,
        headers: headers
    }, function (error, response, body) {
        callback(error, response, body, filenames['themes']);
    });

    request({
        url: url_distributions,
        headers: headers
    }, function (error, response, body) {
        callback(error, response, body, filenames['distributions']);
    });
};

function callback(error, response, body, filepath) {
    if (!error && response.statusCode == 200) {
        $ = cheerio.load(body);
        var project_links = [];
        $('#block-system-main div.view-content li.views-row a').each(function(index, el) {
            var href = $(el).attr('href');
            project_links.push(href);
        });

        var start = '/project/'.length;

        var package_names_all = project_links.map(function(item) {
            return 'drupal/' + item.slice(start);
        });

        var mapping_dictionary = {
            'drupal/gtm-0': 'drupal/gtm',
            'drupal/cision_block': 'drupal/cision_block-cision_block',
            'drupal/twig': 'drupal/twig-twig'
        };

        var package_names_mapped = package_names_all.map(function(item) {
            if (item in mapping_dictionary) {
                return mapping_dictionary[item];
            }
            return item;
        });

        package_names_mapped = package_names_mapped.sort();

        jsonfile.writeFile(filepath, package_names_mapped, {spaces: 2}, function (err) {
            console.error(err)
        });
    }
}


var getLocalPackages = function (filepath) {
    jsonfile.readFile(filenames['composer'], function (err, obj) {
        var packages = Object.keys(obj.require);
        required_drupal_packages = packages.filter(function (item) {
            return item.indexOf('drupal/') !== -1;
        });
        required_drupal_packages = required_drupal_packages.sort();
        jsonfile.writeFile(filepath, required_drupal_packages, {spaces: 2}, function (err) {
            console.error(err)
        });
    });
};

var mergeRemote = function (filepath) {
    jsonfile.readFile(filenames['modules'], function (err, modules) {
        jsonfile.readFile(filenames['themes'], function (err, themes) {
            packages = modules.concat(themes);
            packages = packages.sort();
            jsonfile.writeFile(filepath, packages, {spaces: 2}, function (err) {
                if (err) {
                    return console.error(err);
                }
            });
        });

    });
};

var diff = function () {
    jsonfile.readFile(filenames['packages_remote'], function (err, packages_remote) {
        jsonfile.readFile(filenames['packages_local'], function (err, packages_local) {
            jsonfile.readFile(filenames['packages_ignore'], function (err, packages_ignore) {
                packages = packages_local.concat(packages_ignore);
                packages_new =  packages_remote.filter(function (item) {
                    return packages.indexOf(item) == -1;
                });

                packages_new = packages_new.sort();
                jsonfile.writeFile(filenames['packages_new'], packages_new, {spaces: 2}, function (err) {
                    if(err) {
                        return console.log(err);
                    }
                    console.dir("The file was saved!");

                });
            });

        });

    });
};

var git = function () {
    var start = 'drupal/'.length;
    jsonfile.readFile(filenames['distributions'], function (err, packages) {
        var commands = packages.map(function(item) {
            return 'git clone https://git.drupal.org/project/' + item.slice(start) + '.git';
        });
        commands = commands.sort();
        var content = commands.join('\n');
        fs.writeFile(filenames['contributions_commands'], content, function(err) {
            if(err) {
                return console.log(err);
            }
            console.dir("The file was saved!");
        }); 
    });
};

var sortPackages = function() {
    jsonfile.readFile(filenames['packages_ignore'], function (err, packages) {
        packages = packages.sort();
        jsonfile.writeFile(filenames['packages_ignore'], packages, {spaces: 2}, function(err) {
            if(err) {
                return console.log(err);
            }
            console.dir("The file was saved!");
        }); 
    });
}

var getStablePackages = function () {
    jsonfile.readFile(filenames['composer'], function (err, obj) {
        // var packages = Object.keys(obj.require);

        var obj_clone = Object.create(obj);

        var packages = {};

        var dev_packages = {};
        var alpha_packages = {};
        var beta_packages = {};
        var rc_packages = {};

        for(var k in obj_clone.require) {
            var version = obj_clone.require[k];
            if (version.indexOf('dev') == -1 && version.indexOf('alpha') == -1) {
                packages[k] = version;
                if (version.indexOf('beta') != -1) {
                    beta_packages[k] = version;
                };
                if (version.indexOf('RC') != -1) {
                    rc_packages[k] = version;
                };
            }
            else {
                if (version.indexOf('dev') != -1) {
                    dev_packages[k] = version;
                };
                if (version.indexOf('alpha') != -1) {
                    alpha_packages[k] = version;
                };
            }
        }

        obj.require = packages;
        jsonfile.writeFile('composer.json', obj, {spaces: 4}, function (err) {
            console.error(err)
        });

        jsonfile.writeFile(filenames['packages_dev'], Object.keys(dev_packages), {spaces: 2}, function (err) {
            console.error(err)
        });

        jsonfile.writeFile(filenames['packages_alpha'], Object.keys(alpha_packages), {spaces: 2}, function (err) {
            console.error(err)
        });
        jsonfile.writeFile(filenames['packages_beta'], Object.keys(beta_packages), {spaces: 2}, function (err) {
            console.error(err)
        });

        jsonfile.writeFile(filenames['packages_rc'], Object.keys(rc_packages), {spaces: 2}, function (err) {
            console.error(err)
        });

    });
}

var yargs = require('yargs').command({
    command: 'remote',
    aliases: ['ra'],
    desc: 'All modules, themes distribuitons',
    handler: function () {
        'use strict';
        getRemoteAll();
    }
}).command({
    command: 'local',
    aliases: ['la'],
    desc: 'All installed local packages',
    handler: function () {
        'use strict';
        getLocalPackages(filenames['packages_local']);
    }
}).command({
    command: 'merge',
    aliases: ['m'],
    desc: 'Merge remote modules and themes',
    handler: function () {
        'use strict';
        mergeRemote(filenames['packages_remote']);
    }
}).command({
    command: 'diff',
    aliases: ['d'],
    desc: 'Packages need to be added',
    handler: function () {
        'use strict';
        diff();
    }
}).command({
    command: 'git',
    aliases: ['g'],
    desc: 'Batch clone distributions.',
    handler: function () {
        'use strict';
        git();
    }
}).command({
    command: 'sort',
    aliases: ['s'],
    desc: 'Sort and store.',
    handler: function () {
        'use strict';
        sortPackages();
    }
}).command({
    command: 'stable',
    aliases: ['st'],
    desc: 'Remove dev/alpha/beta/rc packages.',
    handler: function () {
        'use strict';
        getStablePackages();
    }
}).demandCommand().help('h').argv;
