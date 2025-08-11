/**
 *   Jenkins build script for HC Web/Mobile framework with laminas
 *
 */

pipeline {
    agent {
        label 'DockerV1'
    }

    options {
        // This is required if you want to clean before build
        skipDefaultCheckout(true)
        disableConcurrentBuilds()
    }

    environment {
        containerRegistry='jack.hc-sc.gc.ca'
    }

    stages {
        stage('appmeta Info') {
            steps {
                // Clean before build
                cleanWs()
                // We need to explicitly checkout from SCM here
                checkout scm
                script {
                    version="b" + (env.BUILD_ID ? env.BUILD_ID : "MANUAL-BUILD")
                    // Setup Artifactory connection
                    artifactoryServer = Artifactory.server 'default'
                    artifactoryGradle = Artifactory.newGradleBuild()
                    artifactoryDocker = Artifactory.docker server: artifactoryServer
                    buildInfo = Artifactory.newBuildInfo()
                }
            }
        }

/*
        stage('Unit Tests') {
            steps {
                sh '/var/www/vendor/bin/phpunit'
                xunit([
                    thresholds: [
                        failed ( failureThreshold: "0" ),
                        skipped ( unstableThreshold: "0" )
                    ],
                    tools: [
                        PHPUnit(pattern: 'build/logs/junit.xml', stopProcessingIfError: true, failIfNotNew: true)
                    ]
                ])
                publishHTML([
                    allowMissing: false,
                    alwaysLinkToLastBuild: false,
                    keepAll: false,
                    reportDir: 'build/coverage',
                    reportFiles: 'index.html',
                    reportName: 'Coverage Report (HTML)',
                    reportTitles: ''
                ])
                discoverGitReferenceBuild()
                recordCoverage(tools: [[parser: 'COBERTURA', pattern: 'build/logs/cobertura.xml']])
            }
        }
/**/
        stage('Docker Image') {
            when {
                branch 'master'
            }
            steps {
                withCredentials([
                    usernamePassword(credentialsId:'ARTIFACTORY_PUBLISH', usernameVariable: 'USR', passwordVariable: 'PWD')
                ]) {
                    sh """
                        docker login -u ${USR} -p ${PWD} ${containerRegistry}

                        docker build --pull -t php-base-laminas:8.3${version} -t php-base-laminas:8.3 -t php-base-laminas:latest -f dockerfile83 .
                        docker tag php-base-laminas:8.3 ${containerRegistry}/php/php-base-laminas:8.3
                        docker tag php-base-laminas:8.3${version} ${containerRegistry}/php/php-base-laminas:8.3${version}
                        docker tag php-base-laminas:latest ${containerRegistry}/php/php-base-laminas:latest

                        docker build --pull -t php-base-laminas:8.3${version}-mongodb -t php-base-laminas:8.3-mongodb -t php-base-laminas:latest-mongodb -f dockerfile83-mongodb .
                        docker tag php-base-laminas:8.3${version}-mongodb ${containerRegistry}/php/php-base-laminas:8.3${version}-mongodb
                        docker tag php-base-laminas:8.3-mongodb ${containerRegistry}/php/php-base-laminas:8.3-mongodb
                        docker tag php-base-laminas:latest-mongodb ${containerRegistry}/php/php-base-laminas:latest-mongodb
                    """
                }
                script {
                    def buildInfoTemp
                    buildInfoTemp = artifactoryDocker.push "${containerRegistry}/php/php-base-laminas:8.3", 'docker-local'
                    buildInfo.append buildInfoTemp
                    buildInfoTemp = artifactoryDocker.push "${containerRegistry}/php/php-base-laminas:8.3${version}", 'docker-local'
                    buildInfo.append buildInfoTemp
                    buildInfoTemp = artifactoryDocker.push "${containerRegistry}/php/php-base-laminas:8.3-mongodb", 'docker-local'
                    buildInfo.append buildInfoTemp
                    buildInfoTemp = artifactoryDocker.push "${containerRegistry}/php/php-base-laminas:8.3${version}-mongodb", 'docker-local'
                    buildInfo.append buildInfoTemp

                    buildInfoTemp = artifactoryDocker.push "${containerRegistry}/php/php-base-laminas:latest", 'docker-local'
                    buildInfo.append buildInfoTemp
                    buildInfoTemp = artifactoryDocker.push "${containerRegistry}/php/php-base-laminas:latest-mongodb", 'docker-local'
                    buildInfo.append buildInfoTemp
                }
            }
        }
    }

    post {
        always {
            // Clean after build
            cleanWs(cleanWhenNotBuilt: true,
                deleteDirs: true,
                disableDeferredWipeout: true,
                notFailBuild: true,
                patterns: [
                    [pattern: '.gitignore', type: 'INCLUDE']
                ]
            )
            sh """
                docker rmi php-base-laminas:8.3
                docker rmi php-base-laminas:8.3${version}
                docker rmi php-base-laminas:latest
                docker rmi php-base-laminas:8.3${version}-mongodb
                docker rmi php-base-laminas:8.3-mongodb
                docker rmi php-base-laminas:latest-mongodb
            """

            script {
                resultString = "None"
            }
        }
        success {
            script {
                resultString = "Success 🌞"
            }
        }
        unstable {
            script {
                resultString = "Unstable ⛅"
            }
        }
        failure {
            script {
                resultString = "Failure 🌩"
            }
        }
        cleanup {
            emailext body: "<strong>${resultString}</strong><p>See build result details at: <a href='${env.JOB_URL}'>${env.JOB_URL}</a></p>", mimeType: 'text/html; charset=UTF-8', recipientProviders: [[$class: 'CulpritsRecipientProvider'], [$class: 'DevelopersRecipientProvider'], [$class: 'UpstreamComitterRecipientProvider'], [$class: 'RequesterRecipientProvider']], replyTo: 'devops@hc-sc.gc.ca', subject: "${resultString} ${currentBuild.fullDisplayName}"
            script {
                jiraIssueSelector(issueSelector: [$class: 'DefaultIssueSelector'])
                        .each {
                    id -> jiraComment body: "*Build Result ${resultString}* appmeta: ${version} [Details|${env.BUILD_URL}]", issueKey: id
                }
            }
        }
    }
}
