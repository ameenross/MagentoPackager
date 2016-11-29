module.exports = function(grunt) {
    // Project configuration.
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        phpcs: {
            application: {
                src: ['src/', 'bin/magepkg'],
            },
            options: {
                bin: 'vendor/bin/phpcs',
                standard: 'PSR2',
            },
        },
        phplint: {
            good: ['src/**/*.php', 'bin/magepkg'],
        },
        exec: {
            apigen: 'vendor/apigen/apigen/bin/apigen generate -s src/ -d docs/ --template-theme=bootstrap',
        },
        githooks: {
            all: {
                'pre-commit': 'default',
            },
        },
    });

    // PHP code linter.
    grunt.loadNpmTasks('grunt-phplint');
    grunt.registerTask('lint', ['phplint:good']);

    // PHP code sniffer.
    grunt.loadNpmTasks('grunt-phpcs');
    grunt.registerTask('cs', ['phpcs']);

    // Apigen.
    grunt.loadNpmTasks('grunt-exec');
    grunt.registerTask('doc', ['exec:apigen']);

    // Install git hooks.
    grunt.loadNpmTasks('grunt-githooks');

    // Default task, running linter and codesniffer.
    grunt.registerTask('default', ['phplint:good', 'phpcs']);
};
