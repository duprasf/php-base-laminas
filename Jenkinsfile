/**
 *   Jenkins build script for HC Web/Mobile framework with laminas
 *
 */

pipeline {
    agent {
        /*label 'standardv1'*/
        label 'dockerv1'
    }

    options { disableConcurrentBuilds() }

    /*
    parameters {
        string(name: 'FRAMEWORK_CONFIG_FILE_PATH', defaultValue: '/run/secrets/framework-secrets.sh', description: 'Location of the framework config file on the VM')
    }
    /* */

    environment {
        containerRegistry = 'jack.hc-sc.gc.ca'
        containerRegistryPull = 'jack.hc-sc.gc.ca'
    }

    stages {

        stage('appmeta Info') {
            steps {
                checkout scm
                script {

                    def properties = readProperties  file: 'appmeta.properties'

                    //Get basic meta-data
                    rootGroup = properties.root_group
                    rootVersion = properties.root_version
                    buildId = env.BUILD_ID
                    version = rootVersion + "-" + (buildId ? buildId : "MANUAL-BUILD")
                    module = rootGroup

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
                expression {
                    BRANCH_NAME == 'master'
                }
            }
            steps {
                sh 'rm -rf ./docker/code'
                sh 'cp -r ./code ./docker'

                withCredentials([
                    usernamePassword(credentialsId:'ARTIFACTORY_PUBLISH', usernameVariable: 'USR', passwordVariable: 'PWD')
                ]) {
                    sh """
                        docker login -u ${USR} -p ${PWD} ${
                            containerRegistry
                        }
                        docker build -t php-base-laminas:${version} -t php-base-laminas:latest ./docker/
                        docker tag php-base-laminas:${version} ${containerRegistry}/php/php-base-laminas:${version}
                    """
                }
                script {
                    def buildInfoTemp
                    buildInfoTemp = artifactoryDocker.push "${containerRegistry}/php/php-base-laminas:${version}", 'docker-local'
                    buildInfo.append buildInfoTemp
                }
            }
        }
    }

    post {
        always {
            script {
                resultString = "None"
            }
        }
        success {
            script {
                resultString = "Success"
            }
        }
        unstable {
            script {
                resultString = "Unstable"
            }
        }
        failure {
            script {
                resultString = "Failure"
            }
        }
        cleanup {
            emailext body: "<p>See build result details at: <a href='${env.JOB_URL}'>${env.JOB_URL}</a></p>", mimeType: 'text/html; charset=UTF-8', recipientProviders: [[$class: 'CulpritsRecipientProvider'], [$class: 'DevelopersRecipientProvider'], [$class: 'UpstreamComitterRecipientProvider'], [$class: 'RequesterRecipientProvider']], replyTo: 'no-reply@build.scs-lab.com', subject: "${currentBuild.fullDisplayName} ${resultString}"
            script {
                jiraIssueSelector(issueSelector: [$class: 'DefaultIssueSelector'])
                        .each {
                    id -> jiraComment body: "*Build Result ${resultString}* Module: ${module} appmeta: ${version} [Details|${env.BUILD_URL}]", issueKey: id
                }
            }
        }
    }
}
