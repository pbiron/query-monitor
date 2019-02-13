module.exports = function (grunt) {
	'use strict';

	require('load-grunt-tasks')(grunt);

    var pkg = grunt.file.readJSON('package.json');
	var gig = require('gitignore-globs');
	var gag = require('gitattributes-globs');
	var ignored_gitignore = gig('.gitignore', { negate: true } ).map(function(value) {
		return value.replace(/^!\//,'!');
	});
    var ignored_gitattributes = gag( '.gitattributes', { negate: true } ).map(function(value) {
		return value.replace(/^!\//,'!');
	});
	var config = {};

	config.pkg = pkg;

	config.clean = {
		main: [
			'<%= wp_deploy.deploy.options.build_dir %>'
		],
		css: [
			'assets/*.css'
		]
	};

	config.copy = {
		main: {
			src: [
				'**',
				'!.*',
				'!.git/**',
				'!<%= wp_deploy.deploy.options.assets_dir %>/**',
				'!<%= wp_deploy.deploy.options.build_dir %>/**',
				'!README.md',
				'!wiki/**',
				ignored_gitignore,
				ignored_gitattributes
			],
			dest: '<%= wp_deploy.deploy.options.build_dir %>/'
		}
	};

	config.sass = {
		dist: {
			options: {
				sourcemap: 'none',
				style: 'expanded'
			},
			files: {
				'assets/query-monitor-dark.css': 'assets/query-monitor-dark.scss',
				'assets/query-monitor.css': 'assets/query-monitor.scss'
			}
		}
	};

	config.watch = {
		options: {
			interval: 1000
		},
		css: {
			files: '**/*.scss',
			tasks: ['sass']
		}
	};

	config.wp_deploy = {
		deploy: {
			options: {
				svn_user: 'johnbillion',
				plugin_slug: '<%= pkg.name %>',
				build_dir: 'build',
				assets_dir: 'assets-wp-repo'
			}
		}
	};

	grunt.initConfig(config);

	grunt.registerTask('build', [
		'clean',
		'copy'
	]);

	grunt.registerTask('deploy', [
		'build',
		'wp_deploy'
	]);

	grunt.registerTask('default', [
		'watch'
	]);
};
