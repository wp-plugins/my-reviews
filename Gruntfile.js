module.exports = function ( grunt ) {
    grunt.initConfig( {
        pkg: grunt.file.readJSON( 'package.json' ),
        concat: {
            js: {
                src: [
                    'js/admin.js'
                ],
                dest: 'build/js/admin-concat.js'
            }
        },
        uglify: {
            js: {
                files: {
                    'build/js/admin-concat.min.js': ['build/js/admin-concat.js']
                }
            }
        },
        sass: {
            dist: {
                files: {
                    'build/css/my-reviews.css' : 'includes/template/css/my-reviews.scss',
                    'build/css/admin.css' : 'css/admin.scss'
                }
            }
        },
        cssmin: {
            frontcss: {
                src: 'build/css/my-reviews.css',
                dest: 'build/css/my-reviews.min.css'
            },
            backcss: {
                src: 'build/css/admin.css',
                dest: 'build/css/admin.min.css'
            }
        },
        jshint: {
            options: {
                smarttabs: true
            },
            beforeconcat: [
                'js/admin.js'
            ],
            afterconcat: ['build/js/admin-concat.js']
        },
        watch: {
			files: [
                'js/*',
                'css/*',
                'includes/template/css/*'
            ],
			tasks: ['concat', 'uglify', 'sass', 'cssmin:frontcss', 'cssmin:backcss']
		}
    });
    grunt.loadNpmTasks( 'grunt-contrib-concat' );
    grunt.loadNpmTasks( 'grunt-contrib-uglify' );
    grunt.loadNpmTasks( 'grunt-contrib-sass' );
    grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
    grunt.loadNpmTasks( 'grunt-contrib-jshint' );
    grunt.loadNpmTasks( 'grunt-contrib-watch' );
    grunt.registerTask( 'default', ['concat:js', 'uglify:js', 'sass', 'cssmin:frontcss', 'cssmin:backcss'] );
};