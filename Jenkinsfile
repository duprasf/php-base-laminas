/**
 *   Jenkins build script for HC Web/Mobile framework with laminas
 *
 */

pipeline {
    agent {
        label 'StandardV1'
    }
    options {
        // This is required if you want to clean before build
        skipDefaultCheckout(true)
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

                        docker build --pull -t php-base-laminas:8.2${version} -t php-base-laminas:8.2 -f dockerfile82 .
                        docker tag php-base-laminas:8.2 ${containerRegistry}/php/php-base-laminas:8.2
                        docker tag php-base-laminas:8.2${version} ${containerRegistry}/php/php-base-laminas:8.2${version}

                        docker build --pull -t php-base-laminas:8.3${version} -t php-base-laminas:8.3 -t php-base-laminas:latest -f dockerfile83 .
                        docker tag php-base-laminas:8.3 ${containerRegistry}/php/php-base-laminas:8.3
                        docker tag php-base-laminas:8.3${version} ${containerRegistry}/php/php-base-laminas:8.3${version}
                        docker tag php-base-laminas:latest ${containerRegistry}/php/php-base-laminas:latest

                        docker build --pull -t php-base-laminas:8.2${version}-mongodb -t php-base-laminas:8.2-mongodb -f dockerfile82-mongodb .
                        docker tag php-base-laminas:8.2-mongodb ${containerRegistry}/php/php-base-laminas:8.2-mongodb
                        docker tag php-base-laminas:8.2${version}-mongodb ${containerRegistry}/php/php-base-laminas:8.2${version}-mongodb

                        docker build --pull -t php-base-laminas:8.3${version}-mongodb -t php-base-laminas:8.3-mongodb -t php-base-laminas:latest-mongodb -f dockerfile83-mongodb .
                        docker tag php-base-laminas:8.3${version}-mongodb ${containerRegistry}/php/php-base-laminas:8.3${version}-mongodb
                        docker tag php-base-laminas:8.3-mongodb ${containerRegistry}/php/php-base-laminas:8.3-mongodb
                        docker tag php-base-laminas:latest-mongodb ${containerRegistry}/php/php-base-laminas:latest-mongodb
                    """
                }
                script {
                    def buildInfoTemp
                    buildInfoTemp = artifactoryDocker.push "${containerRegistry}/php/php-base-laminas:8.2", 'docker-local'
                    buildInfo.append buildInfoTemp
                    buildInfoTemp = artifactoryDocker.push "${containerRegistry}/php/php-base-laminas:8.2${version}", 'docker-local'
                    buildInfo.append buildInfoTemp
                    buildInfoTemp = artifactoryDocker.push "${containerRegistry}/php/php-base-laminas:8.2-mongodb", 'docker-local'
                    buildInfo.append buildInfoTemp
                    buildInfoTemp = artifactoryDocker.push "${containerRegistry}/php/php-base-laminas:8.2${version}-mongodb", 'docker-local'
                    buildInfo.append buildInfoTemp

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
            cleanWs()
            script {
                resultString = "None"
            }
        }
        success {
            script {
                resultString = "Success ☼"
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
